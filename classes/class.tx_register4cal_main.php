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
 * Main functions for extension 'register4cal' 
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 *
 * Modifications
 * ThEr160909	0.4.0	Initial development of class, coding comes from several other classes
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('register4cal').'classes/class.tx_register4cal_user1.php'); 

class tx_register4cal_main extends tslib_pibase {	
	var $prefixId = 'tx_register4cal_main';					// Same as class name
	var $scriptRelPath = 'classes/class.tx_register4cal_main.php'; 		// Path to this script relative to the extension dir.
	var $extKey = 'register4cal';						// The extension key.
	var $pi_checkCHash = true;
	
	private $settings = Array();						//Array containing typoscript settings
	public $rendering;							//Instance of rendering class
	var $debug = FALSE;							//Flag: Debugging?
	
	/*
         * Constructor for class tx_register4cal_main
         *
         * @return	nothing
         */	
	public function tx_register4cal_main() {
		//Init class
		parent::tslib_pibase();
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->cObj = new tslib_cObj();
		
		//init settings
		$tsconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.'];
		$this->settings['template_file'] = $tsconf['template'];
		$this->settings['date_format'] = $tsconf['dateformat'];
		$this->settings['time_format'] = $tsconf['timeformat'];
		$this->settings['onetimepid'] = $tsconf['onetimepid'];
		$this->settings['onetimereturnparam'] = $tsconf['onetimereturnparam'];
		$this->settings['loginpid'] = $tsconf['loginpid'];
		$this->settings['loginreturnparam'] = $tsconf['loginreturnparam'];
		$this->settings['eventpid'] = $tsconf['view.']['eventViewPid'];
		$this->settings['adminusers'] = explode(',',$tsconf['view.']['adminUsers']);
		$this->settings['language'] = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];
		$this->settings['mailconf'] = $tsconf['emails.'];
		$this->settings['default'] = $tsconf['forms.']['default.'];
		$this->settings['forms'] = $tsconf['forms.'];

		//init userfields
		$this->settings['userfields'] = $tsconf['userfields.'];	
		
		//read the template file
		$this->settings['template'] = $this->cObj->fileResource($this->settings['template_file']);

		//Instanciate rendering class
		require_once(t3lib_extMgm::extPath('register4cal').'classes/class.tx_register4cal_render.php');
		$tx_register4cal_render = &t3lib_div::makeInstanceClassName('tx_register4cal_render');
		$this->rendering = new $tx_register4cal_render($this, $this->settings);
	}


	/***********************************************************************************************************************************************************************
	*
	* Registration form for event list view
	*
	**********************************************************************************************************************************************************************/
	
	/*
	 * Handle registration form for event list view (registration form, registration confirmation or
	 * registration notice, depending on status)
	 *
	 * @param   	Array		$event: Event data
	 *
	 * @return  array  HTML to display
	 */
	public function ListViewRegistrationEvent($event) {
		if ($this->isRegistrationEnabled($event, $event['start_date'])) {
			$this->rendering->setView('list');
			$this->rendering->setEvent($event);
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
			
			//check if user has already registered
			if (!$this->isUserAlreadyRegistered($event['uid'],$event['start_date'],$GLOBALS['TSFE']->fe_user->user['uid'],$event['pid'])) {
				//User has not yet registered for this event. Show the registration form
				$content = $this->renderListRegistrationEVENT();
				//Count registration form in session
				$GLOBALS['TSFE']->fe_user->fetchSessionData();
				$count = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_register4cal_listeventcount');
				$count = $count + 1;
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_register4cal_listeventcount', $count);
				$GLOBALS['TSFE']->fe_user->storeSessionData();
			} else {
				//User has already registered for this event. Show this information.
				$content = $this->renderListRegistrationDetails();
			}				
		}
		return $content;
	}

	/*
	 * Surround content by simple form referring to the same page
	 *
	 * @param	String	$content: Content to be surrounded
	 *
	 * @return	String	$content surrounded by simple form
	 */
	public function ListViewRegistrationForm($content) {
		return '<form action="" method="post">'.$content.'</form>';
	}

	/*
	 * Return html for submit-button
	 *
	 * @return	String	Html for submit buton
	 *
	 */
	public function ListViewRegistrationSubmit() {
		//read listeventcount from sesseion
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();
		$count = $GLOBALS['TSFE']->fe_user->getKey('ses','tx_register4cal_listeventcount');
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_register4cal_listeventcount', 0);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
		
		//show submit button if at least one event with registration form is being displayed
		if ($count > 0) return $this->renderListRegistrationSubmit();
	}

	/*
	 * Store registrations, entered in list view
	 *
	 * @param	Array	$data: piVars from list view (tx_register4cal_main)
	 *
	 */
	public function ListViewRegistrationStore($data) {
		foreach($data as $uid => $events) {
			if (is_array($events))	foreach($events as $getdate => $registration) {
				if ($registration['register'] == 1) {
					$event = $this->readEventRecord($uid);
					$this->rendering->setEvent($event);
					$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
					$registration['uid'] = $uid;
					$registration['getdate'] = $getdate;
					$this->piVars = $registration;
					if ($this->storeData($registration,$event['title'] ,$event['pid'] )) {
						//... storing sucessful -->Send emails and show registration confirmation
						if ($this->debug) t3lib_div::debug('sendNotificationMail','Action');
						$notificationSent = $this->sendNotificationMail();
						if ($this->debug) t3lib_div::debug('sendConfirmationMail','Action');
						$confirmationSent = $this->sendConfirmationMail();
					}
					unset($this->registration->piVars);
				}
			}
		}
	}
	
	
	/***********************************************************************************************************************************************************************
	*
	* Registration form for single event view
	*
	**********************************************************************************************************************************************************************/
	
	/*
	* Handle registration form for single event view (registration form, registration confirmation or
	* registration notice, depending on status)
	*
	* @param   	Array		$data:Get/Post-Data from cal extension (event uid, ...)
	*
	* @return  array  HTML to display
	*/
	public function SingleEventRegistration($data) {
		$event = $this->readEventRecord($data['uid']);
		if ($this->isRegistrationEnabled($event, $data['getdate'])) {

			if ($this->debug) t3lib_div::debug($data, 'Cal Data');
			if ($this->debug) t3lib_div::debug($this->piVars,'Reg4Cal Data');
			$this->rendering->setView('single');
			$this->rendering->setEvent($event);
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
			if (!$this->isUserAlreadyRegistered($data['uid'],$data['getdate'],$GLOBALS['TSFE']->fe_user->user['uid'],$event['pid'])) {
				if ($this->piVars['cmd'] == 'register') {
					//User provided registration information. Try to store the stuff ...
					if ($this->debug) t3lib_div::debug('StoreData','Action');
					if ($this->storeData($data,$event['title'] ,$event['pid'] )) {
						//... storing sucessful -->Send emails and show registration confirmation
						if ($this->debug) t3lib_div::debug('sendNotificationMail','Action');
						$notificationSent = $this->sendNotificationMail();
						if ($this->debug) t3lib_div::debug('sendConfirmationMail','Action');
						$confirmationSent = $this->sendConfirmationMail();
						if ($this->debug) t3lib_div::debug('renderRegistrationConfirmation','Action');
						$content = $this->renderRegistrationConfirmation();
					} else {
						//... storing failed -->Show registration form again
						if ($this->debug) t3lib_div::debug('renderRegistrationForm','Action');
						$content = $this->renderRegistrationForm();
					}
				} else {
					//User has not yet registered for this event. Show the registration form
					if ($this->debug) t3lib_div::debug('renderRegistrationForm','Action');
					$content = $this->renderRegistrationForm();
				}
			} else {
				//User has already registered for this event. Show this information.
				if ($this->debug) t3lib_div::debug('renderRegistrationDetails','Action');
				$content = $this->renderRegistrationDetails();
			}
		}
		return $content;
	}

	/*
	* Login is needed, display information 
	*
	* @return  array  HTML to display
	*/
	public function SingleEventLogin() {
		return $this->renderNeedLoginForm();
	}

	/***********************************************************************************************************************************************************************
	 *
	 * Participants list
	 *
	 **********************************************************************************************************************************************************************/
	/*
         * Renders the list of participants for all events, the current fe-user is allowed to see        
	 * 
	 * @param	String		$pidlist: List of pids from which registrations should be read
         *
         * @return 	string		HTML for participants list
         */
	function ParticipantList($pidlist) {
		$processed_events = Array();
		$feUserId = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		if ($feUserId != 0) {
			$isAdminUser = in_Array($feUserId, $this->settings['adminusers']);
			$noitems.=$this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','NOITEMS');
			//get all registrations
			$select = 'tx_register4cal_registrations.*';
			$table = 'tx_register4cal_registrations';
			$where = 'tx_register4cal_registrations.cal_event_getdate>='.date('Ymd').' and'.
				 ' tx_register4cal_registrations.pid IN ('.$pidlist.')'.
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
							 ' (tx_cal_event.start_date='.$row_registration['cal_event_getdate'].' OR'.	/* Either registration for the event directly */
							 '  tx_cal_event.freq <> \'none\') AND'.					/* or recurring event */
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
				 ' tx_cal_event.start_date>='.date('Ymd').' AND'.				/* Event in the future */
				 ' tx_cal_event.tx_register4cal_activate = 1'.					/* Registration activated */
				 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
			$orderBy = 'start_date ASC';
			$res_event = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where, '', $orderBy);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_event) != 0) {
				$this->rendering->unsetUser();
				$this->rendering->unsetRegistration();
				while ($row_event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_event)) {
					if (!$isAdminUser && !in_Array($feUserId,explode(',',$row_event['tx_register4cal_feUserId']))) continue;
					$this->rendering->setEvent($row_event);
					$eventlist[$row_event['start_date'].$row_event['uid']]= $this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','EVENTENTRY').$noitems;
				}
			}
			if (!isset($eventlist)) $eventlist=$this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','NOEVENTS');
		} else {
			$eventlist=$this->rendering->renderForm('PARTICIPANT_LIST', 'participantList', 'show','NOLOGIN');
		}
		//Final rendering (sort the items before)
		if (is_array($eventlist)) ksort($eventlist);
		$PresetSubparts = Array();
		$PresetSubparts['###NOLOGIN###'] = '';
		$PresetSubparts['###NOEVENTS###'] = '';
		$PresetSubparts['###ITEMS###'] = '';
		$PresetSubparts['###NOITEMS###'] = '';
		$PresetSubparts['###EVENTENTRY###'] = is_array($eventlist) ? implode($eventlist) : $eventlist;
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
	 * @param	String		$pidlist: List of pids from which registrations should be read
         *
         * @return 	string		HTML for event list
         */
	function EventList($pidlist) {
		$feUserId = $GLOBALS['TSFE']->fe_user->user['uid'];
		if ($feUserId != 0) {
			$isAdminUser = in_Array($feUserId, $this->settings['adminusers']);
			
			//user is the current user in this case
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
			
			//get registrations
			$select = 'tx_register4cal_registrations.*';
			$table = 'tx_register4cal_registrations';
			$where = 'tx_register4cal_registrations.feuser_uid='.intval($feUserId).' AND'.
				 ' tx_register4cal_registrations.cal_event_getdate>='.date('Ymd').' AND'.
				 ' tx_register4cal_registrations.pid IN ('.$pidlist.')'.
				 $this->cObj->enableFields('tx_register4cal_registrations');
			$orderBy = 'tx_register4cal_registrations.cal_event_getdate ASC';
			$res_registration = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table,$where,$groupBy ,$orderBy,$limit);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_registration) != 0) {
				while ($row_registration = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_registration)) {
					$this->rendering->setRegistration($row_registration);
					
					//get the event and render ist
					$select = 'tx_cal_event.*';
					$table = 'tx_cal_event';
					$where = 'tx_cal_event.uid = '.$row_registration['cal_event_uid'].' AND '.
						 ' (tx_cal_event.start_date='.$row_registration['cal_event_getdate'].' OR'.     /* Either registration for the event */
						 '  tx_cal_event.freq <> \'none\')'.                                    	/* or recurring event */
						 $this->cObj->enableFields('tx_cal_event');
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
			$nologin = '';
		} else {
			$items = '';
			$nologin = $this->rendering->renderForm('EVENT_LIST', 'eventList', 'show','NOLOGIN');
		}
		//Final rendering
		if ($items=='' && $nologin=='') $noitems=$this->rendering->renderForm('EVENT_LIST', 'eventList', 'show','NOITEMS');
		$PresetSubparts = Array();
		$PresetSubparts['###ITEMS###'] = $items;
		$PresetSubparts['###NOITEMS###'] = $noitems;
		$PresetSubparts['###NOLOGIN###'] = $nologin;
		$content = $this->rendering->renderForm('EVENT_LIST', 'eventList', 'show','',$PresetSubparts);
		
		return $content;
	}


	/***********************************************************************************************************************************************************************
	*
	* Public supporting functions
	*
	**********************************************************************************************************************************************************************/
	
	/*
         * Read an event record from the database
         *
	 * @param	integer		$eventUid: uid of the event to read
	 *
         * @return	array		Event record
         */
	public function readEventRecord($eventUid) {
		$select = 'tx_cal_event.*';
		$table = 'tx_cal_event';
		$where = 'tx_cal_event.uid='.intval($eventUid);
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		
		return $event;
	}

	/*
	 * Check if registration for an event is enabled and if we are in the registration period
	 *
	 * @param	array	$event: Array containing event data
	 * @param	integer	$getDate: Getdate value (for recurring events)
	 *
	 * return	boolean: TRUE: registration is enabled, FALSE: registration is disabled
	 */
	public function isRegistrationEnabled($event, $getDate) {
		if ($event['tx_register4cal_activate'] == 1) {
			$start = $event['tx_register4cal_regstart'];
			$ende = $event['tx_register4cal_regende'];
			$now = time();
			$start = isset($start) ? $start : $now;
			$ende = isset($ende) ? $ende : strtotime($getDate);
			$regEnabled = ($start <= $now && $ende >= $now);
		} else {
			$regEnabled = FALSE;
		}
		if ($this->debug) t3lib_div::debug($regEnabled ? 'Ja' : 'Nein','isRegistrationEnabled');
		return $regEnabled;
	}

	/*
	* Check if user has alreay registered for the event and store the registration record if this is the case
	*
	* @param	integer		$eventUid: UID of the event for which the registration should be checked
	* @param	integer		$eventGetDate: getDate of the event for which the registration should be checked
	* @param	integer		$userUid: UID if the user for which the registration should be checked
	* @param	integer		$eventPid: PID of the page containing the registrations
	* @return  	boolean  	TRUE: User has already registered, FALSE, User has not yet registered
	*/
	public function isUserAlreadyRegistered($eventUid, $eventGetDate, $userUid, $eventPid) {
		$select = '*';
		$table = 'tx_register4cal_registrations';
		$where = 'cal_event_uid='.intval($eventUid).
			 ' AND'. ' cal_event_getdate='.intval($eventGetDate).
			 ' AND'. ' feuser_uid='.intval($userUid).
			 ' AND'. ' pid='.intval($eventPid).
			 $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = '';
		$groupBy = '';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy , $orderBy, $limit);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
			$alreadyReg = false;
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$this->rendering->setRegistration($row);
			$alreadyReg = true;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		if ($this->debug) t3lib_div::debug($alreadyReg ? 'Ja' : 'Nein','isUserAlreadyRegistered');
		return $alreadyReg;
	}


	/***********************************************************************************************************************************************************************
	*
	* Private registration functions
	*
	**********************************************************************************************************************************************************************/

	/*
	* Store the registration in the database
	*
	* @param	array		$data: Data for the registration
	* @param	string		$eventTitle: Title of the event
	* @param	integer		$eventPid: Pid where the event (and the registration) is stored
	*
	* @return  boolean  TRUE: Registration sucessfully stored,  FALSE: Registration not stored
	*/
	private function storeData($data, $eventTitle, $eventPid) {
		if (! $this->isUserAlreadyRegistered($data['uid'], $data['getdate'], $GLOBALS['TSFE']->fe_user->user['uid'], $eventPid)) {
			//Prepare additional fields
			$addfields = Array();
			$userfields = $this->settings['userfields'];
			if (is_array($userfields)) {
				foreach ($userfields as $field) {
					$addfield = Array();
					$addfield['conf'] = $field;
					$addfield['value'] = $this->piVars['FIELD_'.$field['name']];
					$addfields[$field['name']] = $addfield;
				}
			}
			 
			//write registration record
			$recordlabel = tx_register4cal_user1::formatDate($data['getdate'], 0, $this->settings['date_format']).' '.$eventTitle.': '.$GLOBALS['TSFE']->fe_user->user['name'];
			$write = Array();
			$write['pid'] = intval($eventPid);
			$write['tstamp'] = time();
			$write['crdate'] = time();
			$write['recordlabel'] = $recordlabel;
			$write['cruser_id'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
			$write['cal_event_uid'] = intval($data['uid']);
			$write['cal_event_getdate'] = intval($data['getdate']);
			$write['feuser_uid'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
			$write['additional_data'] = serialize($addfields);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_register4cal_registrations', $write);
			 
			$this->rendering->setRegistration($write);
			 
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/*
	* Render the registration form
	*
	* @return  string  Registration form
	*/
	private function renderRegistrationForm() {
		return $this->rendering->renderForm('###REGISTRATION_FORM###', 'registrationForm', 'edit');
	}
	 
	/*
	* Render the registration confirmation
	*
	* @return  string  Registration confirmation
	*/
	private function renderRegistrationConfirmation() {
		return $this->rendering->renderForm('###CONFIRMATION_FORM###', 'confirmationForm', 'show');
	}
	 
	/*
	* Render the registration details
	*
	* @return  string  Registration details
	*/
	private function renderRegistrationDetails() {
		return $this->rendering->renderForm('###ALREADY_REGISTERED###', 'alreadyRegistered', 'show');
	}


	/*
	 * Render the "need to login" form
	 *
	 * @return   string  "Need to login" form
	 */
	private function renderNeedLoginForm() {
		return $this->rendering->renderForm('###NEEDLOGIN_FORM###', 'needloginForm', 'show');
	}
	
	/*
	* Render the registration form
	*
	* @return  string  Registration form
	*/
	function renderListRegistrationSubmit() {
		return $this->rendering->renderForm('###LIST_REGISTRATION_SUBMIT###', 'listRegistrationSubmit', 'edit');
	}
	 
	/*
	* Render the registration form
	*
	* @return  string  Registration form
	*/
	function renderListRegistrationEvent() {
		return $this->rendering->renderForm('###LIST_REGISTRATION_EVENT###', 'listRegistrationEvent', 'edit');
	}	 
	 
	/*
	* Render the registration details
	*
	* @return  string  Registration details
	*/
	function renderListRegistrationDetails() {
		return $this->rendering->renderForm('###LIST_ALREADY_REGISTERED###', 'listAlreadyRegistered', 'show');
	}	
	
	/*
	* Send confirmation email to the user (if wanted and email address of user is available)
	*
	* @return  boolean  TRUE: email sent,  FALSE: email not sent
	*/
	private function sendConfirmationMail() {
		//Send email if it should be sent and we have the email of the fe-user
		$mailconf = $this->settings['mailconf'];
		if ($mailconf['sendConfirmationMail'] == 1 && $GLOBALS['TSFE']->fe_user->user['email']) {
			//render email
			$content = $this->rendering->renderForm('###CONFIRMATION_MAIL###', 'confirmationMail', 'show');
			$subject = $this->rendering->renderSubject('confirmationMail');
			 
			//send email
			$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$htmlmail->start();
			$htmlmail->subject = $subject;
			$htmlmail->from_email = $mailconf['senderAddress'];
			$htmlmail->CharSet = 'UTF-8';
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
	* @return  boolean  TRUE: email sent,  FALSE: email not sent
	*/
	private function sendNotificationMail() {
		//Concatenate organizer email and admin email if necessary
		$mailconf = $this->settings['mailconf'];
		if ($this->settings['organizer_email'] != '' && $mailconf['adminAddress'] != '') {
			$mailTo = $this->settings['organizer_email'].','.$mailconf['adminAddress'];
		} else {
			$mailTo = $this->settings['organizer_email'].$mailconf['adminAddress'];
		}
		 
		//Send email if it should be sent and we have at least one email adress given
		if ($mailconf['sendNotificationMail'] == 1 && ($mailTo != '')) {
			//render email
			$content = $this->rendering->renderForm('###NOTIFICATION_MAIL###', 'notificationMail', 'show');
			$subject = $this->rendering->renderSubject('notificationMail');
			 
			//send email
			$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$htmlmail->start();
			$htmlmail->subject = $subject;
			$htmlmail->from_email = $mailconf['senderAddress'];
			$htmlmail->CharSet = 'UTF-8';
			$htmlmail->from_name = $mailconf['senderName'];
			$htmlmail->replyto_email = $htmlmail->from_email;
			$htmlmail->replyto_name = $htmlmail->from_name;
			$htmlmail->setHtml($content);
			$htmlmail->setHeaders();
			$htmlmail->setContent();
			$htmlmail->setRecipient(explode(',', $mailTo));
			$htmlmail->sendTheMail();
			 
			$result = TRUE;
		} else {
			$result = FALSE;
		}
		 
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_main.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_main.php']);
}
        
?>