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
 * THER230209	0.1.0	Initial development of class
 * THER010309   0.2.0	Moved general functions to class tx_register4cal_user1
 * 			Registration form moved to class tx_register4cal_hook1, called now via hook from cal extension
 * THER080409	0.2.3	List of participants was always empty unless displayed in admin mode
 */ 

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('register4cal').'user/class.tx_register4cal_user1.php'); 

/**
 * Plugin 'Registration for Cal-Events' for the 'register4cal' extension.
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_register4cal_pi1';			// Same as class name
	var $scriptRelPath = 'pi1/class.tx_register4cal_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'register4cal';				// The extension key.
	var $pi_checkCHash = true;
	var $data          = Array();					//Array for internal data
	var $general;
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm();
	
		//Instanciate general class
		$this->general = t3lib_div::makeInstance('tx_register4cal_user1');
	
		$this->data['displayMode'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'displayMode');
	
		$this->initData();     	
		
		switch ($this->data['displayMode']) {

			case 2:
				$content = $this->EventList_main();
				break;
			case 3:
				$content = $this->ParticipantList_main();
				break;
			default:
				$content = 'Something went terribly wrong with extenstion register4cal. You better tell the admin ...';
				break;
		}
		
		return $this->pi_wrapInBaseClass($content);
	}

	/*
         * Init all needed data and write it to an array        
         *
         * @return 	nothing
         */
	function initData()  {
		//get ts-config for plugin
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
		
		// flexform Values
		$this->data['pidlist'] = $this->pi_getPidList($this->pi_getFFvalue($this->cObj->data['pi_flexform'],'pages'),$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'recursive'));  

		//read the template file
		$this->data['template'] = $this->cObj->fileResource($this->data['template_file']);
		
		//get piVars from tx_cal_controler
		if ($this->data['displayMode'] == 1) $this->data['cal_piVars'] = t3lib_div::GParrayMerged('tx_cal_controller');
	}

/***********************************************************************************************************************************************************************
 *
 * Participants list
 *
 **********************************************************************************************************************************************************************/
	/*
         * Renders the list of participants for all events, the current fe-user is allowed to see        
         *
         * @return 	string		HTML for participants list
         */
	function ParticipantList_main() {
		$feUserId = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$isAdminUser = in_Array($feUserId, $this->data['adminusers']);
		
		//Template holen 
		$template = $this->cObj->getSubpart($this->data['template'],'###PARTICIPANT_LIST###');
		$subtemplate = $this->cObj->getSubpart($template,'###PARTICIPANT_NOITEMS###');
		$marker['###NOITEMS###'] = $this->pi_getLL('noparticipants');
		$noItems = $this->cObj->substituteMarkerArray($subtemplate, $marker);
		
		//get events
		if ($isAdminUser) {
			$select = 'tx_cal_event.*';
			$table = 'tx_cal_event';
			$where = 'tx_cal_event.tx_register4cal_activate = 1 AND'.				/* Registration activated */
				 ' tx_cal_event.pid IN ('.$this->data['pidlist'].') AND'.			/* PID in given area */
				 ' tx_cal_event.start_date >= '.date('Ymd').					/* Only events starting today or in the future */
				 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
		} else {
			$select = 'tx_cal_event.*, tx_cal_organizer.tx_register4cal_feUserId';
			$table = 'tx_cal_event, tx_cal_organizer';
			$where = ' tx_cal_event.organizer_id = tx_cal_organizer.uid AND'.			/* Join event and organizer */
				 ' tx_cal_organizer.tx_register4cal_feuserId LIKE \'%'.$feUserId.'%\' AND'.	/* UserId somehow in the user (not exact as is also selects feUserId 98 when searching for 9, but ensures that we do not have to loop all records later) */
				 ' tx_cal_event.tx_register4cal_activate = 1 AND'.				/* Registration activated */
				 ' tx_cal_event.pid IN ('.$this->data['pidlist'].') AND'.			/* PID in given area */
				 ' tx_cal_event.start_date >= '.date('Ymd').					/* Only events starting today or in the future */
				 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
		}
		$orderby = 'start_date ASC, start_time_ASC';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) != 0) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				//Check if we really have to display this record
				//if ($isAdminUser || in_Array($feUserId,explode(',',$row['tx_register4cal_feUserView'])))		//ThEr080409
				if ($isAdminUser || in_Array($feUserId,explode(',',$row['tx_register4cal_feUserId'])))			//ThEr080409
					$content .= $this->ParticipantList_renderListForEvent($row, $template, $noItems);
			}
			$noitems = '';
		}
		return $content;
	}

	/*
         * Render the participant information for one event
	 *
	 * @param	array		$event: associative array containing the event record
	 * @param	string		$template to use
	 * @param	string		$noItems: rendered "NOITEMS"-part, used when $eventParticipantItems is empty
	 *      
         * @return 	String		Rendered participant information for the event
         */
	function ParticipantList_renderListForEvent($event, $template, $noItems) {
		$eventHeading = '';
		$eventItems = '';
		$eventCalEventGetdate = 0;
		$template_item = $this->cObj->getSubpart($template,'###PARTICIPANT_ITEM###');
		
		//get the registrations for this event
		$select = 'tx_register4cal_registrations.*, fe_users.name, fe_users.email';
		$table = 'tx_register4cal_registrations, fe_users';
		$where = 	'tx_register4cal_registrations.cal_event_uid='.intval($event['uid']).
				' AND tx_register4cal_registrations.feuser_uid=fe_users.uid'.
				$this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = 'tx_register4cal_registrations.cal_event_getdate, fe_users.name';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) != 0) while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			if ($eventCalEventGetdate != $row['cal_event_getdate']) {
				//Add items to event, if we have an event
				if ($eventHeading != '') $content .= $this->ParticipantList_addParticipantsToHeading($eventHeading, $eventItems, $noItems);
	
				//Render the new event heading and prepare for the new items
				$eventHeading = $this->ParticipantList_renderEventHeading($event, $row, $template);
				$eventCalEventGetdate = $row['cal_event_getdate'];
				$eventItems = '';
			}
			
			//render participant item
			$marker = Array();
			$marker['###NAME###'] = $row['name'];
			$marker['###EMAIL###'] = $row['email'];
			$eventItems .= $this->cObj->substituteMarkerArray($template_item,$marker);
		} else {
			$eventHeading = $this->ParticipantList_renderEventHeading($event, array(), $template);
		}
		//Add items to event, if we have an event
		if ($eventHeading != '') $content .= $this->ParticipantList_addParticipantsToHeading($eventHeading, $eventItems, $noItems);

		return $content;
	}

	/*
         * renders the heading if the participants list for one event
         *
	 * @param	array		$event: associative array containing the event record
	 * @param	array		$row: associative array containing one registration record for the event
	 * @param	string		$template to use
	 *
         * @return 	string		HTML for heading of participants list
         */
	function ParticipantList_renderEventHeading($event, $row, $template) {
		$this->general->formatDateTime($event, $row, $formatedStart, $formatedEnd, $this->data['date_format'], $this->data['time_format'], $this->pi_getLL('event_allday'));

		$this->general->getOrganizerData($event, $this->data['organizer_name'], $this->data['organizer_email']);

		//Get template and render it
		$marker = Array();
		$marker['###EVENT_HEADING###'] = htmlspecialchars($this->pi_getLL('event_heading'));
		$marker['###LABEL_TITLE###'] = htmlspecialchars($this->pi_getLL('event_label_title'));
		$marker['###LABEL_START###'] = htmlspecialchars($this->pi_getLL('event_label_start'));
		$marker['###LABEL_END###'] = htmlspecialchars($this->pi_getLL('event_label_end'));
		$marker['###LABEL_ORGANIZER###'] = htmlspecialchars($this->pi_getLL('event_label_organizer'));
		$marker['###LINK###'] = $this->getEventLink($event, $row);
		$marker['###LINK_TEXT###'] = htmlspecialchars($this->pi_getLL('event_link_text'));
		$marker['###TITLE###'] = htmlspecialchars($event['title']);
		$marker['###START###'] = htmlspecialchars($formatedStart);
		$marker['###END###'] = htmlspecialchars($formatedEnd);
		$marker['###ORGANIZER###'] = htmlspecialchars($this->data['organizer_name']);
		$marker['###LABEL_NAME###'] = htmlspecialchars($this->pi_getLL('user_label_name'));
		$marker['###LABEL_EMAIL###'] = htmlspecialchars($this->pi_getLL('user_label_email'));
		$content .= $this->cObj->substituteMarkerArray($template,$marker);
		
		return $content;
	}

	/*
         * Adds the participant lines to the participant list for one event        
         *
	 * @param	string		$eventHeading: rendered heading for the participants list of one event
	 * @param	string		$eventParticipantItems: rendered item lines for the participants of this event
	 * @param	string		$noItems: rendered "NOITEMS"-part, used when $eventParticipantItems is empty
	 *
         * @return 	string		HTML for participants list
         */
	function ParticipantList_addParticipantsToHeading($eventHeading, $eventParticipants, $noItems) {
		$eventHeading = $this->cObj->substituteSubpart($eventHeading,'###PARTICIPANT_ITEM###',$eventParticipants);
		if ($eventParticipants != '') {
			$eventHeading = $this->cObj->substituteSubpart($eventHeading,'###PARTICIPANT_NOITEMS###','');
		} else {
			$eventHeading = $this->cObj->substituteSubpart($eventHeading,'###PARTICIPANT_NOITEMS###',$noItems);
		}
		
		return $eventHeading;
	}

/***********************************************************************************************************************************************************************
 *
 * List of events for which an user has registered
 *
 **********************************************************************************************************************************************************************/
	
	/*
         * Renders the list of events, for which the current fe-user has registered
         *
         * @return 	string		HTML for event list
         */
	function EventList_main() {
		$feUserId = $GLOBALS['TSFE']->fe_user->user['uid'];
		$isAdminUser = in_Array($feUserId, $this->data['adminusers']);
		
		//Template holen 
		$template = $this->cObj->getSubpart($this->data['template'],'###EVENT_LIST###');
		
		//Anmeldungen holen
		$select = 'tx_cal_event.*, tx_register4cal_registrations.*';
		$table = 'tx_cal_event, tx_register4cal_registrations';
		$where = 'tx_cal_event.uid = tx_register4cal_registrations.cal_event_uid AND'.
			 ' tx_register4cal_registrations.feuser_uid='.intval($feUserId).' AND'.
			 ' tx_register4cal_registrations.cal_event_getdate>='.date('Ymd').
			 $this->cObj->enableFields('tx_cal_event').
			 $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = 'tx_cal_event.start_date ASC, tx_cal_event.start_time ASC';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy,$limit);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
			$subtemplate = $this->cObj->getSubpart($template,'###EVENT_NOITEMS###');
			$marker['###NOITEMS###'] = $this->pi_getLL('noevents');
			$noitems = $this->cObj->substituteMarkerArray($subtemplate, $marker);
			$items = '';
		} else {
			$subtemplate = $this->cObj->getSubpart($template,'###EVENT_ITEM###');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				//render the item
				$this->general->formatDateTime($row, $row, $formatedStart, $formatedEnd, $this->data['date_format'], $this->data['time_format'], $this->pi_getLL('event_allday'));
				$this->general->getOrganizerData($row, $this->data['organizer_name'], $this->data['organizer_email']);
				
				$marker = Array();
				$marker['###LINK###'] = $this->getEventLink($row, $row);
				$marker['###TITLE###'] = htmlspecialchars($row['title']);
				$marker['###START###'] = htmlspecialchars($formatedStart);
				$marker['###END###'] = htmlspecialchars($formatedEnd);
				$marker['###ORGANIZER###'] = htmlspecialchars($this->data['organizer_name']);
				$items .= $this->cObj->substituteMarkerArray($subtemplate,$marker);				
			}
			$noitems = '';
		}
		$marker = array();
		$marker['###EVENT_HEADING###'] = htmlspecialchars($this->pi_getLL('event_heading'));
		$marker['###LABEL_TITLE###'] = htmlspecialchars($this->pi_getLL('event_label_title'));
		$marker['###LABEL_START###'] = htmlspecialchars($this->pi_getLL('event_label_start'));
		$marker['###LABEL_END###'] = htmlspecialchars($this->pi_getLL('event_label_end'));
		$marker['###LABEL_ORGANIZER###'] = htmlspecialchars($this->pi_getLL('event_label_organizer'));
		$content = $this->cObj->substituteMarkerArray($template,$marker);
		
		$content = $this->cObj->substituteSubpart($content,'###EVENT_ITEM###',$items);
		$content = $this->cObj->substituteSubpart($content,'###EVENT_NOITEMS###',$noitems);
		
		return $content;
	}
	
/***********************************************************************************************************************************************************************
 *
 * Helper functions
 *
 **********************************************************************************************************************************************************************/	
	
	/*
         * Get the link to the event single view
	 *
	 * Hint:
	 *  If event- and registration information have been retrieved in one select, they are contained in a single associative array.
	 *  In this case, this array can be assigned to both $eventRow and $registrationRow
         *
	 * @param	array		$eventRow: associative array containing the event record
	 * @param	array		$registrationRow: associative array containing the registration record for the event
	 *
         * @return 	string		Link target for event single view
         */	
	function getEventLink($eventRow, $RegistrationRow) {
		$vars = array();
		$vars['tx_cal_controller[view]']='event';
		$vars['tx_cal_controller[type]']='tx_cal_phpicalendar';

		if (empty($RegistrationRow)) {
			$vars['tx_cal_controller[getdate]']=intval($eventRow['start_date']);
			$vars['tx_cal_controller[uid]']=intval($eventRow['uid']);
		} else {
			$vars['tx_cal_controller[getdate]']=intval($RegistrationRow['cal_event_getdate']);
			$vars['tx_cal_controller[uid]']=intval($RegistrationRow['cal_event_uid']);
		}
		
		return $this->pi_getPageLink($this->data['eventpid'],'',$vars);
	}
	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php']);
}
        
?>
