<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Thomas Ernst <typo3@thernst.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

/**
 * Modifications
 * ThEr230209	0.1.0	Initial development of class
 * ThEr010309   0.2.0	Registration form now called via hook
 * ThEr130409	0.2.5	got warning "Call-time pass-by-reference has been deprecated"
 */ 

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('register4cal').'user/class.tx_register4cal_user1.php'); 

/**
 * Process hook  'preFinishViewRendering'  to display the registration form 
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_hook1 extends tslib_pibase {
	var $prefixId      = 'tx_register4cal_hook1';			// Same as class name
	var $scriptRelPath = 'pi1/class.tx_register4cal_hook1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'register4cal';				// The extension key.
	var $pi_checkCHash = true;
	var $data          = Array();					//Array for internal data
	var $general;
	
/***********************************************************************************************************************************************************************
 *
 * Hook from extension cal to add the registration form
 *
 **********************************************************************************************************************************************************************/
	function preFinishViewRendering() {}
	
	function postSearchForObjectMarker($otherThis, &$content) {
		//get piVars from tx_cal_controler
		$this->data['cal_piVars'] = t3lib_div::GParrayMerged('tx_cal_controller');

		//Conditions to display the registration form (first step)
		if ($this->data['cal_piVars']['view']=='event' 					/* This is the single view of an event                          */
		    && $this->data['cal_piVars']['uid']!=0 					/* An event is being displayed					*/
		    && $GLOBALS['TSFE']->fe_user->user['uid'] != 0) {				/* An frontend user is logged in 				*/
		    
			//Now read event record
			$this->data['event'] = $this->RegistrationForm_getEventRow($this->data['cal_piVars']['uid']);
		
			//Check if registration is enabled and if we are in the registration period
			if ($this->data['event']['tx_register4cal_activate']==1) {
				$start = $this->data['event']['tx_register4cal_regstart'];
				$ende  = $this->data['event']['tx_register4cal_regende'];
				$now   = time();
				$start = isset($start) ? $start : $now;
				$ende  = isset($ende) ? $ende : strtotime($this->data['cal_piVars']['getdate']);
				$regEnabled =  ($start<=$now && $ende>=$now);
			} else {
				$regEnabled = FALSE;
			}		    
		    
			if ($regEnabled) {
				//Init pibase functons
				$this->cObj = $otherThis->cObj;
				$this->pi_setPiVarDefaults();
				$this->pi_loadLL();
				
				//Instanciate general class
				$this->general = t3lib_div::makeInstance('tx_register4cal_user1');
				
				//get other configuration
				$tsconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.'];
				$this->data['template_file'] = $tsconf['template'];
				$this->data['date_format'] = $tsconf['dateformat'];
				$this->data['time_format'] = $tsconf['timeformat'];
				$this->data['eventpid'] = $tsconf['view.']['eventViewPid'];
				$this->data['adminusers'] = explode(',',$tsconf['view.']['adminUsers']);
				$this->data['userConfirmationMail'] = $tsconf['registration.']['userConfirmationMail.'];
				$this->data['organizerNotificationMail'] = $tsconf['registration.']['organizerNotificationMail.'];
				$this->data['fieldlist'] = $tsconf['registration.']['additional_fields.'];
				$this->data['language'] = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];

				//read the template file
				$this->data['template'] = $this->cObj->fileResource($this->data['template_file']);
		
				//render registration and add it to the content
				$content .= $this->RegistrationForm_main();
			}
		}
	}	

/***********************************************************************************************************************************************************************
 *
 * Registration form
 *
 **********************************************************************************************************************************************************************/



	/*
         * Main function for rendering the registration form, registration confirmation or registration notice
	 *      
         * @return 	array		HTML to display
         */
	function RegistrationForm_main() {
		$this->general->getOrganizerData($this->data['event'], $this->data['organizer_name'], $this->data['organizer_email']);
		
		if ($this->piVars['cmd'] == 'register') {
			//User provided registration information. Try to store the stuff ...
			if ($this->RegistrationForm_StoreData()) {
				//... storing sucessful --> Send emails and show registration confirmation
				$notificationSent = $this->RegistrationForm_sendNotificationMail();
				$confirmationSent = $this->RegistrationForm_sendConfirmationMail();
				$content = $this->RegistrationForm_renderRegistrationConfirmation($notificationSent, $confirmationSent);
			} else {
				//... storing failed --> Show registration form again
				$content = $this->RegistrationForm_renderRegistrationForm();
			}
		} else {			
			if (!$this->RegistrationForm_isUserAlreadyRegistered()) {
				//User has not yet registered for this event. Show the registration form
				$content = $this->RegistrationForm_renderRegistrationForm();
			} else {
				//User has already registered for this event. Show this information.
				$content = $this->RegistrationForm_renderRegistrationDetails();
			}
		}

		return $content;
	}
	
	/*
         * Read the event record from the database
	 *      
         * @param  	integer		$eventId: uid of the event to be read
	 *
         * @return 	array		array containing the fields of the record
         */
	function RegistrationForm_getEventRow($eventId) {
                $select = 'tx_cal_calendar.uid AS calendar_uid, ' .
                        'tx_cal_calendar.owner AS calendar_owner, ' .
                        'tx_cal_event.*';
                $table = 'tx_cal_event, tx_cal_calendar';
                $where = 'tx_cal_calendar.uid = tx_cal_event.calendar_id AND tx_cal_event.uid='.intval($eventId);
                $orderBy = 'tx_cal_event.start_date ASC, tx_cal_event.start_time ASC';
                $groupBy = 'uid';
                $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		return($row);
	}

        /*
         * Check if user has alreay registered for the event and store the registration record if this is the case
         *
         * @return 	boolean		TRUE: User has already registered, FALSE, User has not yet registered
         */
	function RegistrationForm_isUserAlreadyRegistered() {
                $select = '*'; 
                $table = 'tx_register4cal_registrations';
                $where =  'cal_event_uid='.intval($this->data['cal_piVars']['uid']).' AND'.
			  ' cal_event_getdate='.intval($this->data['cal_piVars']['getdate']).' AND'.
			  ' feuser_uid='.intval($GLOBALS['TSFE']->fe_user->user['uid']).
			  $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = '';
                $groupBy = '';
                $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy,$limit);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$this->data['registration'] = $row;
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
			$alreadyReg = false;
		} else $alreadyReg = true;
		return $alreadyReg;
	}
	
/*
         * Render the registration form         
         *
         * @return 	string		Registration form
         */
	function RegistrationForm_renderRegistrationForm() {
		//additional fields
		if (is_array($this->data['fieldlist'])) {
			foreach ($this->data['fieldlist'] as $field) {
				$template = $this->cObj->getSubpart($this->data['template'],'###INPUT_'.strtoupper($field['type']).'###');
				if ($template != '') {
					$caption = $field['caption.'][$this->data['language']] != '' ? $field['caption.'][$this->data['language']] : $field['caption.']['default'];
					$fieldname = 'ADDFIELD_'.$field['name'];
					$marker = Array();
					$marker['###SIZE###'] = htmlspecialchars(isset($field['size']) ? $field['size'] : 20);
					$marker['###NAME###'] = htmlspecialchars($this->prefixId.'['.$fieldname.']');
					$marker['###VALUE###'] =  htmlspecialchars(isset($this->piVars[$fieldname]) ? $this->piVars[$fieldname] : $field['default']);
					$marker['###CAPTION###'] = htmlspecialchars($caption);
					$additional_fields .= $this->cObj->substituteMarkerArray($template,$marker);
				}
			}
		}
		
		//other (hidden) fields (mainly piVars from the cal-extension)
		reset($this->data['cal_piVars']);
		while (list($key, $val) = each($this->data['cal_piVars'])) {
			$additional_fields.='<input type="hidden" name="tx_cal_controller['.htmlspecialchars($key).']" value="'.htmlspecialchars($val).'">';
		    }
		$additional_fields .= '<input type="hidden" name="'.$this->prefixId.'[cmd]" value="register">';
		
		//put everything together
		$template = $this->cObj->getSubpart($this->data['template'],'###REGISTRATION_FORM###');
		$marker = Array();
		$marker['###HEADING###'] = htmlspecialchars($this->pi_getLL('registrationform_heading'));
		$marker['###TEXT_TOP###'] = htmlspecialchars($this->pi_getLL('registrationform_text_top'));
		$marker['###TEXT_BOTTOM###'] = htmlspecialchars($this->pi_getLL('registrationform_text_bottom'));
		$marker['###LINK###'] = htmlspecialchars($this->pi_linkTP_keepPIvars_url());
		$marker['###ADDITIONAL_FIELDS###'] = $additional_fields;
		$marker['###LABEL_SUBMIT###'] = htmlspecialchars($this->pi_getLL('registrationform_label_submit'));
		$content = $this->cObj->substituteMarkerArray($template, $marker);

		return $content;
	}
	
        /*
         * Store the registration in the database
         *
         * @return 	boolean		TRUE: Registration sucessfully stored,  FALSE: Registration not stored
         */
	function RegistrationForm_StoreData() {
		//Prepare additional fields
		$addfields = Array();
		if (is_array($this->data['fieldlist'])) {
			foreach ($this->data['fieldlist'] as $field) {
				$addfield = Array();
				$addfield['name'] = $field['name'];
				$addfield['type'] = $field['type'];
				$addfield['size'] = $field['size'];
				$addfield['caption'] = $field['caption.'];
				$addfield['value'] = $this->piVars['ADDFIELD_'.$field['name']];
				$addfields[$field['name']] = $addfield;
			}
		}

		//write registration record
		$recordlabel = $this->general->formatDate($this->data['cal_piVars']['getdate'], 0, $this->data['date_format']).' '.$this->data['event']['title'].': '.$GLOBALS['TSFE']->fe_user->user['name'];
		$write = Array();
		$write['pid'] = intval($this->data['event']['pid']);
		$write['tstamp'] = time();
		$write['crdate'] = time();
		$write['recordlabel'] = $recordlabel;
		$write['cruser_id'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$write['cal_event_uid'] = intval($this->data['cal_piVars']['uid']);
		$write['cal_event_getdate'] = intval($this->data['cal_piVars']['getdate']);
		$write['feuser_uid'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$write['additional_data'] = serialize($addfields);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_register4cal_registrations',$write);
		
		$this->data['registration'] = $write;
			
		//ToDo: Ensure that everything went well before returning "TRUE"
		return TRUE;
	}	
	
	/*
         * Render the registration confirmation         
         *
	 * @param	boolean		$notificationSent: Has the notification email been sent?
	 * @param	boolean		$confirmationSent: Has the confirmation email been sent?
         * @return 	string		Registration confirmation
         */
	function RegistrationForm_renderRegistrationConfirmation($notificationSent, $confirmationSent) {	
		$template = $this->cObj->getSubpart($this->data['template'],'###REGISTRATION_DONE###');
		$marker = Array();
		$marker['###HEADING###'] = htmlspecialchars($this->pi_getLL('confirmation_heading'));
		$marker['###TEXT_TOP###'] = htmlspecialchars($this->pi_getLL('confirmation_text_top'));
		$marker['###TEXT_BOTTOM###'] = htmlspecialchars($this->pi_getLL('confirmation_text_bottom'));
		$marker['###ADDITIONAL_FIELDS###'] = $this->RegistrationForm_renderAdditionalFieldsFromRegistration();
		$content = $this->cObj->substituteMarkerArray($template,$marker);
		
		return $content;
	}

	/*
         * Render the registration details         
         *
         * @return 	string		Registration details
         */
	 function RegistrationForm_renderRegistrationDetails() {
		$template = $this->cObj->getSubpart($this->data['template'],'###REGISTRATION_SHOW###');
		$marker = Array();
		$marker['###HEADING###'] = htmlspecialchars($this->pi_getLL('alreadyregistered_heading'));
		$marker['###TEXT_TOP###'] = htmlspecialchars($this->pi_getLL('alreadyregistered_text_top'));
		$marker['###TEXT_BOTTOM###'] = htmlspecialchars($this->pi_getLL('alreadyregistered_text_bottom'));
		$marker['###REGISTRATION_HEADING###'] = htmlspecialchars($this->pi_getLL('registration_heading'));
		$marker['###ADDITIONAL_FIELDS###'] = $this->RegistrationForm_renderAdditionalFieldsFromRegistration();
		$content = $this->cObj->substituteMarkerArray($template,$marker);
		
		return $content;
	}	
	
        /*
         * Takes the "additional_fields" from the registration record and renders them
	 *      
         * @return 	String		Additional Fields in HTML
         */	
	function RegistrationForm_renderAdditionalFieldsFromRegistration() {
		//get additional data
		$additional_data = unserialize($this->data['registration']['additional_data']);
		$additional_fields = '';
		if (is_array($additional_data)) {
                	reset($additional_data);
                	while (list($name, $field) = each($additional_data)) {
				$caption = $field['caption'][$this->data['language']] != '' ? $field['caption'][$this->data['language']] : $field['caption']['default'];
				$template = $this->cObj->getSubpart($this->data['template'],'###SHOW_'.strtoupper($field['type']).'###');
				$marker = array();
				$marker['###SIZE###'] = htmlspecialchars($field['size']);
				$marker['###NAME###'] = htmlspecialchars($field['name']);
				$marker['###VALUE###'] =  htmlspecialchars($field['value']);
				$marker['###CAPTION###'] = htmlspecialchars($caption);
				$additional_fields .= $this->cObj->substituteMarkerArray($template,$marker);				
                	}
		}
		return $additional_fields;
	}
	
	/*
         * Send confirmation email to the user (if wanted and email address of user is available)
         *
         * @return 	boolean		TRUE: email sent,  FALSE: email not sent
         */
	 function RegistrationForm_sendConfirmationMail() {
		//Send email if it should be sent and we have the email of the fe-user
		if ($this->data['userConfirmationMail']['sendit'] == 1 && $GLOBALS['TSFE']->fe_user->user['email']) {
			$template = $this->cObj->getSubpart($this->data['template'],'###EMAIL_USER###');
			$marker = array();
			$marker['###HEADING###'] = htmlspecialchars($this->pi_getLL('confirmation_heading'));
			$marker['###TEXT_TOP###'] = htmlspecialchars($this->pi_getLL('confirmation_text_top'));
			$marker['###TEXT_BOTTOM###'] = htmlspecialchars($this->pi_getLL('confirmation_text_bottom'));
			$marker['###EVENTDETAILS###'] = $this->RegistrationForm_renderEventData();
			$content = $this->cObj->substituteMarkerArray($template,$marker); 
		
			$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$htmlmail->start();
			$htmlmail->subject = $this->pi_getLL('confirmationmail_subject');
			$htmlmail->from_email = $this->data['userConfirmationMail']['senderAddress'];
			$htmlmail->CharSet = "UTF-8";
			$htmlmail->from_name = $this->data['userConfirmationMail']['senderName'];
			$htmlmail->replyto_email = $htmlmail->from_email;
			$htmlmail->replyto_name = $htmlmail->from_name;
			$htmlmail->setHtml($content);
			$htmlmail->setHeaders();
			$htmlmail->setContent();
			$htmlmail->setRecipient($GLOBALS['TSFE']->fe_user->user['email']);
			$htmlmail->sendTheMail();
			
			$result = TRUE;
		} else {
			$result = FALSE;
		}
		
		return $result;
	}
	
        /*
         * Send notification email to the organizer (if wanted and email address is set)
         *
         * @return 	boolean		TRUE: email sent,  FALSE: email not sent
         */
	function RegistrationForm_sendNotificationMail() {
		//Concatenate organizer email and admin email if necessary
		if ($this->data['organizer_email'] != '' && $this->data['organizerNotificationMail']['adminEmail'] != '') {
			$mailTo = $this->data['organizer_email'].','.$this->data['organizerNotificationMail']['adminEmail'];
		} else {
			$mailTo = $this->data['organizer_email'].$this->data['organizerNotificationMail']['adminEmail'];
		}
	
		//Send email if it should be sent and we have at least one email adress given
		if ($this->data['organizerNotificationMail']['sendit'] == 1 && ($mailTo != '')) {
			//get template and render content
			$template = $this->cObj->getSubpart($this->data['template'],'###EMAIL_ORGANIZER###');
			$marker = array();
			$marker['###LABEL_USERNAME###'] = htmlspecialchars($this->pi_getLL('user_label_name'));
			$marker['###LABEL_USEREMAIL###'] = htmlspecialchars($this->pi_getLL('user_label_email'));
			$marker['###HEADING###'] = htmlspecialchars($this->pi_getLL('notification_heading'));
			$marker['###USER_HEADING###'] = htmlspecialchars($this->pi_getLL('notification_heading'));
			$marker['###TEXT_TOP###'] = htmlspecialchars($this->pi_getLL('notification_text_top'));
			$marker['###TEXT_BOTTOM###'] = htmlspecialchars($this->pi_getLL('notification_text_bottom'));
			$marker['###USERNAME###'] = htmlspecialchars($GLOBALS['TSFE']->fe_user->user['name']);
			$marker['###USEREMAIL###'] = htmlspecialchars($GLOBALS['TSFE']->fe_user->user['email']);
			$marker['###EVENTDETAILS###'] = $this->RegistrationForm_renderEventData();
			$content = $this->cObj->substituteMarkerArray($template,$marker); 
		
			//send the mail
			$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$htmlmail->start();
			$htmlmail->subject = $this->pi_getLL('notificationmail_subject');
			$htmlmail->from_email = $this->data['organizerNotificationMail']['senderAddress'];
			$htmlmail->CharSet = "UTF-8";
			$htmlmail->from_name = $this->data['organizerNotificationMail']['senderName'];
			$htmlmail->replyto_email = $htmlmail->from_email;
			$htmlmail->replyto_name = $htmlmail->from_name;
			$htmlmail->setHtml($content);
			$htmlmail->setHeaders();
			$htmlmail->setContent();
			$htmlmail->setRecipient(explode(',',$mailTo));
			$htmlmail->sendTheMail();
			
			$result = TRUE;
		} else {
			$result = FALSE;
		}
		
		return $result;
	}
	
/*
         * Render the information on the event
	 *      
         * @return 	String		Event information in HTML
         */
	function RegistrationForm_renderEventData() {
		//$this->general->FormatDateTime($this->data['event'], $this->data['registration'], $formatedStart, $formatedEnd, $this->data['date_format'], $this->data['time_format'], $this->pi_getLL('event_allday'));	//ThEr130409
		$this->general->FormatDateTime($this->data['event'], $this->data['registration'], $formatedStart, $formatedEnd, $this->data['date_format'], $this->data['time_format'], $this->pi_getLL('event_allday'));	//ThEr130409
		
		//Get template and render it
		$template = $this->cObj->getSubpart($this->data['template'],'###EVENT_SHOW###');
		$marker = Array();
		$marker['###TITLE###'] = htmlspecialchars($this->data['event']['title']);
		$marker['###START###'] = htmlspecialchars($formatedStart);
		$marker['###END###'] = htmlspecialchars($formatedEnd);
		$marker['###ORGANIZER###'] = htmlspecialchars($this->data['organizer_name']. ' ('.$this->data['organizer_email'].')');
		$marker['###EVENT_HEADING###'] = htmlspecialchars($this->pi_getLL('event_heading'));
		$marker['###LABEL_TITLE###'] = htmlspecialchars($this->pi_getLL('event_label_title'));
		$marker['###LABEL_START###'] = htmlspecialchars($this->pi_getLL('event_label_start'));
		$marker['###LABEL_END###'] = htmlspecialchars($this->pi_getLL('event_label_end'));
		$marker['###LABEL_ORGANIZER###'] = htmlspecialchars($this->pi_getLL('event_label_organizer'));
		$marker['###ADDITIONAL_FIELDS###'] = $this->RegistrationForm_renderAdditionalFieldsFromRegistration();
		$content = $this->cObj->substituteMarkerArray($template,$marker);

		return $content;
	}	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_hook1.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_hook1.php']);
}
        
?>
