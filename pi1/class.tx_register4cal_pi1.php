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
 * Plugin 'Registration for Cal-Events' for the 'register4cal' extension.
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 *
 * Modifications
 * ThEr230209	0.1.0	Initial development of class
 * ThEr010309   0.2.0	Moved general functions to class tx_register4cal_user1
 * 			Registration form moved to class tx_register4cal_hook1, called now via hook from cal extension
 * ThEr080409	0.2.3	List of participants was always empty unless displayed in admin mode
 * ThEr020509	0.3.0	Complete revision of extension. Substantial changes in templates, TypoScript, etc.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('register4cal').'user/class.tx_register4cal_render.php'); 

class tx_register4cal_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_register4cal_pi1';			// Same as class name
	var $scriptRelPath = 'pi1/class.tx_register4cal_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'register4cal';				// The extension key.
	var $pi_checkCHash = true;
	var $data          = Array();					//Array for internal data
	//var $general;
	var $rendering;
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
	
		//Instanciate rendering class
		$tx_register4cal_render = &t3lib_div::makeInstanceClassName('tx_register4cal_render');
		$this->rendering = &new $tx_register4cal_render($this);
	
		$this->data['displayMode'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'displayMode');
		$this->data['pidlist'] = $this->pi_getPidList($this->pi_getFFvalue($this->cObj->data['pi_flexform'],'pages'),$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'recursive'));  	
		
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
		$processed_events = Array();
		$feUserId = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$isAdminUser = in_Array($feUserId, $this->rendering->settings['adminusers']);
		$noitems.=$this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','NOITEMS');

		//get all registrations
		$select = 'tx_register4cal_registrations.*';
		$table = 'tx_register4cal_registrations';
		$where = 'tx_register4cal_registrations.cal_event_getdate>='.date('Ymd').' and'.
			 ' tx_register4cal_registrations.pid IN ('.$this->data['pidlist'].')'.
			 $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = 'tx_register4cal_registrations.cal_event_getdate ASC, tx_register4cal_registrations.cal_event_uid ASC';
		$res_registration = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,'' ,$orderBy);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_registration) != 0) {
			while ($row_registration = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_registration)) {
				if ($cur_event_uid != $row_registration['cal_event_uid'] || $cur_event_getdate != $row_registration['cal_event_getdate']) {
					//Huston, we have a new event ...
					//Render the old event and add it to the event array
					if ($cur_event_uid != 0) $eventlist[$cur_event_getdate.$cur_event_uid] = $this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','EVENTENTRY'). ($items=='' ? $noitems : $items);
					
					//reset the event
					unset($cur_event_uid);
					unset($cur_event_getdate);
					unset($items);
					
					//get the new event and check it
					$select = 'tx_cal_event.*, tx_cal_organizer.tx_register4cal_feUserId';
					$table = 'tx_cal_event, tx_cal_organizer';
					$where = ' tx_cal_event.organizer_id = tx_cal_organizer.uid AND'.			/* Join event and organizer */
						 ' tx_cal_event.uid='.$row_registration['cal_event_uid'].' AND'.		/* Select event */ 
						 ' tx_cal_event.tx_register4cal_activate = 1'.					/* Registration activated */
						 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
					$res_event = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_event) == 0) continue;
					if (!($row_event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_event))) continue;
					if (!$isAdminUser && !in_Array($feUserId,explode(',',$row_event['tx_register4cal_feUserId']))) continue;
					$this->rendering->setEvent($row_event);
					
					//store information on this event
					$cur_event_uid = $row_registration['cal_event_uid'];
					$cur_event_getdate = $row_registration['cal_event_getdate'];
					if (!in_array($cur_event_uid, $processed_events)) $processed_events[] = $cur_event_uid;
				}
				$this->rendering->setRegistration($row_registration);
				
				//get the user
				$select = 'fe_users.*';
				$table = 'fe_users';
				$where = 'fe_users.uid='.$row_registration['feuser_uid'].$this->cObj->enableFields('fe_users');
				$orderBy = '';
				$res_user = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_user) == 0) continue;
				if (!($row_user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_user))) continue;
				$this->rendering->setUser($row_user);
				
				//Render the registration entry
				$items.=$this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','ITEMS');
			}
			
			//Render the last event and add it to the event array
			if ($cur_event_uid != 0) $eventlist[$cur_event_getdate.$cur_event_uid] = $this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','EVENTENTRY'). ($items=='' ? $noitems : $items);

		}
		
		//now get the events without registration
		$processed_events_list = (count($processed_events)==0) ? '0' : implode(', ',$processed_events);
		$select = 'tx_cal_event.*, tx_cal_organizer.tx_register4cal_feUserId';
		$table = 'tx_cal_event, tx_cal_organizer';
		$where = ' tx_cal_event.organizer_id = tx_cal_organizer.uid AND'.			/* Join event and organizer */
			 ' tx_cal_event.uid NOT IN ('.$processed_events_list.') AND'.			/* Select events */ 
			 ' tx_cal_event.tx_register4cal_activate = 1'.					/* Registration activated */
			 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
		$orderBy = 'start_date ASC';
		$res_event = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where, '', $orderBy);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_event) != 0) {
			$this->rendering->unsetUser();
			$this->rendering->unsetRegistration();
			while ($row_event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_event)) {
				$this->rendering->setEvent($row_event);
				$eventlist[$row_event['start_date'].$row_event['uid']]= $this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','EVENTENTRY').$noitems;
			}
		}
		
		//Final rendering (sort the items before)
		ksort($eventlist);
		$PresetSubparts = Array();
		$PresetSubparts['###ITEMS###'] = '';
		$PresetSubparts['###NOITEMS###'] = '';
		$PresetSubparts['###EVENTENTRY###'] = implode($eventlist);
		$content .= $this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','',$PresetSubparts);
		
		return $content;
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
		$isAdminUser = in_Array($feUserId, $this->rendering->settings['adminusers']);
		
		//user is the current user in this case
		$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
		
		//get registrations
		$select = 'tx_register4cal_registrations.*';
		$table = 'tx_register4cal_registrations';
		$where = 'tx_register4cal_registrations.feuser_uid='.intval($feUserId).' AND'.
			 ' tx_register4cal_registrations.cal_event_getdate>='.date('Ymd').' AND'.
			 ' tx_register4cal_registrations.pid IN ('.$this->data['pidlist'].')'.
			 $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = 'tx_register4cal_registrations.cal_event_getdate ASC';
		$res_registration = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy,$limit);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_registration) != 0) {
			while ($row_registration = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_registration)) {
				$this->rendering->setRegistration($row_registration);
				
				//get the event and render ist
				$select = 'tx_cal_event.*';
				$table = 'tx_cal_event';
				$where = 'tx_cal_event.uid = '.$row_registration['cal_event_uid'].$this->cObj->enableFields('tx_cal_event');
				$orderBy = '';
				$res_event = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy,$limit);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_event) != 0) {
					while ($row_event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_event)) {
						$this->rendering->setEvent($row_event);
						$items.=$this->rendering->renderForm('EVENT_LIST', 'eventList', 'show','ITEMS');
					}
				}
			}
		}

		//Final rendering
		if ($items=='') $noitems=$this->rendering->renderForm('EVENT_LIST', 'eventList', 'show','NOITEMS');
		$PresetSubparts = Array();
		$PresetSubparts['###ITEMS###'] = $items;
		$PresetSubparts['###NOITEMS###'] = $noitems;
		$content = $this->rendering->renderForm('EVENT_LIST', 'eventList', 'show','',$PresetSubparts);
		
		return $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php']);
}
        
?>