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
 * Display the registration form by processing the hook 'preFinishViewRendering'
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 *
 * Modifications
 * ThEr230209	0.1.0	Initial development of class
 * ThEr010309   0.2.0	Registration form now called via hook
 * ThEr130409	0.2.5	got warning "Call-time pass-by-reference has been deprecated"
 * ThEr020509	0.3.0	Complete revision of extension. Substantial changes in templates, TypoScript, etc.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('register4cal').'user/class.tx_register4cal_render.php'); 

/**
 * 
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
	var $rendering;							//Instance of rendering class
	
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
			$select = 'tx_cal_event.*';
			$table = 'tx_cal_event';
			$where = 'tx_cal_event.uid='.intval($this->data['cal_piVars']['uid']);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where);
			$event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$GLOBALS['TYPO3_DB']->sql_free_result($result);;

			//Check if registration is enabled and if we are in the registration period
			if ($event['tx_register4cal_activate']==1) {
				$start = $event['tx_register4cal_regstart'];
				$ende  = $event['tx_register4cal_regende'];
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
								
				//Instanciate rendering class
				$tx_register4cal_render = &t3lib_div::makeInstanceClassName('tx_register4cal_render');
				$this->rendering = &new $tx_register4cal_render($this);
				$this->rendering->setEvent($event);
				$this->rendering->setUser( $GLOBALS['TSFE']->fe_user->user);

				$this->data['event_title'] = $event['title'];
				$this->data['event_pid'] = $event['pid'];

				//render registration and add it to the content
				$content .= $this->RegistrationForm();
			}
		}
	}	

        /*
         * Check if user has alreay registered for the event and store the registration record if this is the case
         *
         * @return 	boolean		TRUE: User has already registered, FALSE, User has not yet registered
         */
	function isUserAlreadyRegistered() {
                $select = '*'; 
                $table = 'tx_register4cal_registrations';
                $where =  'cal_event_uid='.intval($this->data['cal_piVars']['uid']).' AND'.
			  ' cal_event_getdate='.intval($this->data['cal_piVars']['getdate']).' AND'.
			  ' feuser_uid='.intval($GLOBALS['TSFE']->fe_user->user['uid']).' AND'.
			  ' pid='.intval($this->data['event_pid']).
			  $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = '';
                $groupBy = '';
                $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy,$limit);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
			$alreadyReg = false;
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$this->rendering->setRegistration($row);
			$alreadyReg = true;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		return $alreadyReg;
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
	function RegistrationForm() {
		if (!$this->isUserAlreadyRegistered()) {
			if ($this->piVars['cmd'] == 'register') {
				//User provided registration information. Try to store the stuff ...
				if ($this->StoreData()) {
					//... storing sucessful --> Send emails and show registration confirmation
					$notificationSent = $this->sendNotificationMail();
					$confirmationSent = $this->sendConfirmationMail();
					$content = $this->renderRegistrationConfirmation();
				} else {
					//... storing failed --> Show registration form again
					$content = $this->renderRegistrationForm();
				}
			} else {			
				//User has not yet registered for this event. Show the registration form
				$content = $this->renderRegistrationForm();
			}
		} else {
			//User has already registered for this event. Show this information.
			$content = $this->renderRegistrationDetails();
		}

		return $content;
	}
	
	/*
         * Render the registration form         
         *
         * @return 	string		Registration form
         */
	function renderRegistrationForm() {
		return $this->rendering->renderForm('###REGISTRATION_FORM###', 'registrationForm', 'edit');
	}

	/*
         * Render the registration confirmation         
         *
         * @return 	string		Registration confirmation
         */
	function renderRegistrationConfirmation() {	
		return $this->rendering->renderForm('###CONFIRMATION_FORM###', 'confirmationForm','show');
	}

	/*
         * Render the registration details         
         *
         * @return 	string		Registration details
         */
	 function renderRegistrationDetails() {
		return $this->rendering->renderForm('###ALREADY_REGISTERED###', 'alreadyRegistered','show');
	}	
	
        /*
         * Store the registration in the database
         *
         * @return 	boolean		TRUE: Registration sucessfully stored,  FALSE: Registration not stored
         */
	function StoreData() {
		//Prepare additional fields
		$addfields = Array();
		$userfields = $this->rendering->settings['userfields'];
		if (is_array($userfields)) {
			foreach ($userfields as $field) {
				$addfield = Array();
				$addfield['conf'] = $field;
				$addfield['value'] = $this->piVars['FIELD_'.$field['name']];
				$addfields[$field['name']] = $addfield;
			}
		}

		//write registration record
		$recordlabel = tx_register4cal_user1::formatDate($this->data['cal_piVars']['getdate'], 0, $this->data['date_format']).' '.$this->data['event_title'].': '.$GLOBALS['TSFE']->fe_user->user['name'];
		$write = Array();
		$write['pid'] = intval($this->data['event_pid']);
		$write['tstamp'] = time();
		$write['crdate'] = time();
		$write['recordlabel'] = $recordlabel;
		$write['cruser_id'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$write['cal_event_uid'] = intval($this->data['cal_piVars']['uid']);
		$write['cal_event_getdate'] = intval($this->data['cal_piVars']['getdate']);
		$write['feuser_uid'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$write['additional_data'] = serialize($addfields);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_register4cal_registrations',$write);
		
		$this->rendering->setRegistration($write);
			
		//ToDo: Ensure that everything went well before returning "TRUE"
		return TRUE;
	}	

	/*
         * Send confirmation email to the user (if wanted and email address of user is available)
         *
         * @return 	boolean		TRUE: email sent,  FALSE: email not sent
         */
	 function sendConfirmationMail() {
		//Send email if it should be sent and we have the email of the fe-user
		$mailconf = $this->rendering->settings['mailconf'];
		if ($mailconf['sendConfirmationMail'] == 1 && $GLOBALS['TSFE']->fe_user->user['email']) {
			$content = $this->rendering->renderForm('###CONFIRMATION_MAIL###', 'confirmationMail','show');
			$subject = $this->rendering->renderSubject('confirmationMail');
			
			$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$htmlmail->start();
			$htmlmail->subject = $subject;
			$htmlmail->from_email = $mailconf['senderAddress'];
			$htmlmail->CharSet = "UTF-8";
			$htmlmail->from_name = $mailconf['senderName'];
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
	function sendNotificationMail() {
		//Concatenate organizer email and admin email if necessary
		$mailconf = $this->rendering->settings['mailconf'];
		if ($this->rendering->settings['organizer_email'] != '' && $mailconf['adminAddress'] != '') {
			$mailTo = $this->rendering->settings['organizer_email'].','.$mailconf['adminAddress'];
		} else {
			$mailTo = $this->rendering->settings['organizer_email'].$mailconf['adminAddress'];
		}

		//Send email if it should be sent and we have at least one email adress given
		if ($mailconf['sendNotificationMail'] == 1 && ($mailTo != '')) {
			$content = $this->rendering->renderForm('###NOTIFICATION_MAIL###', 'notificationMail','show');
			$subject = $this->rendering->renderSubject('notificationMail');
			
			//send the mail
			$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$htmlmail->start();
			$htmlmail->subject = $subject;
			$htmlmail->from_email = $mailconf['senderAddress'];
			$htmlmail->CharSet = "UTF-8";
			$htmlmail->from_name = $mailconf['senderName'];
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/hooks/class.tx_register4cal_hook1.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/hooks/class.tx_register4cal_hook1.php']);
}
        
?>