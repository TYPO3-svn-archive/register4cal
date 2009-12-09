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
 * class.tx_register4cal_fehooks.php
 *
 * Provide main functions for extension register4cal
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('register4cal') . 'classes/class.tx_register4cal_user1.php'); 

/**
 * Main functions for extension 'register4cal' 
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_main extends tslib_pibase {	
	var $prefixId = 'tx_register4cal_main';					// Same as class name
	var $scriptRelPath = 'classes/class.tx_register4cal_main.php'; 		// Path to this script relative to the extension dir.
	var $extKey = 'register4cal';						// The extension key.
	var $pi_checkCHash = true;
	
	private $settings = Array();						//Array containing typoscript settings
	public $rendering;							//Instance of rendering class
	
	/*
         * Constructor for class tx_register4cal_main
         *
         * @return	void
         */	
	public function tx_register4cal_main() {
			// Init class
		parent::tslib_pibase();
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->cObj = new tslib_cObj();
		
			// Init settings
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
		$this->settings['disableUnregister'] = $tsconf['disableUnregister'];
		$this->settings['keepUnregistered'] = $tsconf['keepUnregistered'];

			// Init userfields
		$this->settings['userfields'] = $tsconf['userfields.'];	
		
			// Read the template file
		$this->settings['template'] = $this->cObj->fileResource($this->settings['template_file']);

			// Instanciate rendering class
		require_once(t3lib_extMgm::extPath('register4cal') . 'classes/class.tx_register4cal_render.php');
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
	 * @param   	array		$event: Event data
	 *
	 * @return  	array 		HTML to display
	 */
	public function listViewRegistrationEvent($event) {
		if ($this->isRegistrationEnabled($event, $event['start_date'])) {
				// Set propper data to rendering class
			$this->rendering->setView('list');
			$this->rendering->setEvent($event);
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
			
				// Check if user has already registered
			if (!$this->isUserAlreadyRegistered($event['uid'], $event['start_date'], $GLOBALS['TSFE']->fe_user->user['uid'], $event['pid'], $status)) {
					// User has not yet registered for this event. Show the registration form
				$content = $this->renderListRegistrationEvent($event);
					// Count registration form in user's session data
				$GLOBALS['TSFE']->fe_user->fetchSessionData();
				$count = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_register4cal_listeventcount_register');
				$count = $count + 1;
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_register4cal_listeventcount_register', $count);
				$GLOBALS['TSFE']->fe_user->storeSessionData();
			} else {
					// User has already registered for this event. Show this information.
				$content = $this->renderListRegistrationDetails($status);
					// Count registration form in user's session data
				$GLOBALS['TSFE']->fe_user->fetchSessionData();
				$count = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_register4cal_listeventcount_unregister');
				$count = $count + 1;
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_register4cal_listeventcount_unregister', $count);
				$GLOBALS['TSFE']->fe_user->storeSessionData();				
			}				
		}
		return $content;
	}

	/*
	 * Surround content by simple form referring to the same page
	 *
	 * @param	string		$content: Content to be surrounded
	 *
	 * @return	string		$content surrounded by simple form
	 */
	public function listViewRegistrationForm($content) {
		return '<form action="" method="post">' . $content . '</form>';
	}

	/*
	 * Return html for submit-button
	 *
	 * @return	string		Html for submit buton
	 *
	 */
	public function listViewRegistrationSubmit() {
			// Read listeventcount from user's session data
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();
		$countRegister = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_register4cal_listeventcount_register');
		$countUnregister = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_register4cal_listeventcount_unregister');
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_register4cal_listeventcount_register', 0);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_register4cal_listeventcount_unregister', 0);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
		$count = $countRegister + $countUnregister;
		
			// Show submit button if at least one event with registration form is being displayed
		$content =  $count > 0 ? $this->renderListRegistrationSubmit() : '';
		return $content;
	}

	/*
	 * Store registrations, entered in list view
	 *
	 * @param	array		$data: piVars from list view (tx_register4cal_main)
	 *
	 */
	public function listViewRegistrationStore($data) {
		foreach($data as $uid => $events) {
			if (is_array($events))	foreach($events as $getdate => $registration) {
				if ($registration['register'] == 1) {
					$event = $this->readEventRecord($uid);
					$this->rendering->setEvent($event);
					$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
					$registration['uid'] = $uid;
					$registration['getdate'] = $getdate;
					$this->piVars = $registration;
					if ($this->storeData($registration, $event, $status)) {
							// ... storing sucessful -->Send emails and show registration confirmation
						$notificationSent = $this->sendNotificationMail($status,1);
						$confirmationSent = $this->sendConfirmationMail($status,1);
					}
					unset($this->registration->piVars);
				} elseif ($registration['unregister'] == 1) {
				
					$event = $this->readEventRecord($uid);
					$this->rendering->setEvent($event);
					$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
					$registration['uid'] = $uid;
					$registration['getdate'] = $getdate;
					$this->piVars = $registration;
					if ($this->storeDataUnregister($registration, $event, $status)) {
							// ... storing sucessful -->Send emails and show registration confirmation
						$notificationSent = $this->sendNotificationMail($status,2);
						$confirmationSent = $this->sendConfirmationMail($status,2);
						$this->checkWaitlist($getdate, $event);
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
	* @param   	array		$data:Get/Post-Data from cal extension (event uid, ...)
	*
	* @return  	string 		HTML to display
	*/
	public function singleEventRegistration($data) {
		$event = $this->readEventRecord($data['uid']);
		if ($this->isRegistrationEnabled($event, $data['getdate'])) {
				// Set propper data in rendering class
			$this->rendering->setView('single');
			$this->rendering->setEvent($event);
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
				// Render depending on registration status
			$hasRegistered = $this->isUserAlreadyRegistered($data['uid'], $data['getdate'], $GLOBALS['TSFE']->fe_user->user['uid'], $event['pid'], $status);
			if (!$hasRegistered) {
				if ($this->piVars['cmd'] == 'register') {
						// User provided registration information. Try to store the stuff ...
					if ($this->storeData($data, $event, $status)) {
							// ... storing sucessful -->Send emails and show registration confirmation
						$notificationSent = $this->sendNotificationMail($status, 1);
						$confirmationSent = $this->sendConfirmationMail($status, 1);
						$content = $this->renderRegistrationConfirmation($status);
					} else {
							// ... storing failed -->Show registration form again
						$content = $this->renderRegistrationForm($event);
					}					
				} else {
						// User has not yet registered for this event. Show the registration form
					$content = $this->renderRegistrationForm($event);
				}
			} else {
				if ($this->piVars['cmd'] == 'unregister') {
						// Unregister User
					if ($this->storeDataUnregister($data, $event, $status)) {
						$notificationSent = $this->sendNotificationMail($status, 2);
						$confirmationSent = $this->sendConfirmationMail($status, 2);
						$this->checkWaitlist($data['getdate'], $event);
						$content = $this->renderRegistrationForm($event);
					} else {
							// .. unregistering failed --> Show Registration details again
						$content = $this->renderRegistrationDetails($status);
					}
				} else {
						// User has already registered for this event. Show this information.
					$content = $this->renderRegistrationDetails($status);
				}
			}
		}
		return $content;
	}

	/*
	* Login is needed, display information 
	*
	* @return  	string  		HTML to display
	*/
	public function singleEventLogin() {
		return $this->renderNeedLoginForm();
	}

	/***********************************************************************************************************************************************************************
	 *
	 * Participants list
	 *
	 **********************************************************************************************************************************************************************/
	 
	function performCheckWaitlist() {
		$feUserId = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$isAdminUser = in_Array($feUserId, $this->settings['adminusers']);
		$eventUid = intval($this->piVars['uid']);
		$eventGetDate = intval($this->piVars['getdate']);
		
		// check authorization for this functino
		$authorized = $isAdminUser;
		if ($feuserId != 0 && !$authorized) {
			//check event
			$select = 'tx_cal_organizer.tx_register4cal_feUserId';
			$table = 'tx_cal_event, tx_cal_organizer';
			$where = ' tx_cal_event.organizer_id = tx_cal_organizer.uid AND' .			/* Join event and organizer */
				 ' tx_cal_event.uid=' . $eventUid . ' AND' .					/* Select event */ 
				 ' tx_cal_event.tx_register4cal_activate = 1' .					/* Registration activated */
				 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
			$eventRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($eventRes) != 0) {
				$event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($eventRes);
				$authorized  = in_Array($feUserId, explode(',', $event['tx_register4cal_feUserId']));
			}
		}
		
		if ($authorized) {
			// check waitlist for event entry
			$event = $this->readEventRecord($eventUid);
			$this->checkWaitlist($eventGetDate, $event);
		}
	}
	 
	/*
         * Renders the list of participants for all events, the current fe-user is allowed to see        
	 * 
	 * @param	string		$pidlist: List of pids from which registrations should be read
         *
         * @return 	string		HTML for participants list
         */
	function participantList($pidlist) {
			// probably check waitlist entries
		if ($this->piVars['cmd'] == 'checkwaitlist') $this->performCheckWaitlist();

		$config = 'listOutput.attendees';
		$processedEvents = Array();
		$feUserId = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		if ($feUserId != 0) {
			$isAdminUser = in_Array($feUserId, $this->settings['adminusers']);
			$noItems .= $this->rendering->renderForm($config, 'show', 'NOITEMS');
						
				//get all registrations
			$select = 'tx_register4cal_registrations.*';
			$table = 'tx_register4cal_registrations';
			$where = 'tx_register4cal_registrations.cal_event_getdate>=' . date('Ymd') . ' and' .
				 ' tx_register4cal_registrations.pid IN (' . $pidlist . ')' .
				 $this->cObj->enableFields('tx_register4cal_registrations');
			$orderBy = 'tx_register4cal_registrations.cal_event_getdate ASC, tx_register4cal_registrations.cal_event_uid ASC, tx_register4cal_registrations.status ASC';
			$registrationRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, '', $orderBy);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($registrationRes) != 0) {
				while (($registration = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($registrationRes))) {
					if ($curEventUid != $registration['cal_event_uid'] || $curEventGetdate != $registration['cal_event_getdate']) {
							//Huston, we have a new event ...
							//Render the old event and add it to the event array
						if ($curEventUid != 0) 
							$eventList[$curEventGetdate . $curEventUid] = 
								$this->rendering->renderForm($config, 'show', 'EVENTENTRY') .
									($items=='' ? $noItems : $items);
						
							//reset the event
						unset($curEventUid);
						unset($curEventGetdate);
						unset($items);
						
							//get the new event and check it
						$select = 'tx_cal_event.*, tx_cal_organizer.tx_register4cal_feUserId';
						$table = 'tx_cal_event, tx_cal_organizer';
						$where = ' tx_cal_event.organizer_id = tx_cal_organizer.uid AND' .			/* Join event and organizer */
							 ' tx_cal_event.uid=' . $registration['cal_event_uid'] . ' AND' .		/* Select event */ 
							 ' (tx_cal_event.start_date=' . $registration['cal_event_getdate'] . ' OR' .	/* Either registration for the event directly */
							 '  tx_cal_event.freq <> \'none\') AND' .					/* or recurring event */
							 ' tx_cal_event.tx_register4cal_activate = 1' .					/* Registration activated */
							 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
						$eventRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
						if ($GLOBALS['TYPO3_DB']->sql_num_rows($eventRes) == 0) continue;
						if (!($event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($eventRes))) continue;
						if (!$isAdminUser && !in_Array($feUserId, explode(',', $event['tx_register4cal_feUserId']))) continue;
						$this->rendering->setEvent($event);
						
							//store information on this event
						$curEventUid = $registration['cal_event_uid'];
						$curEventGetdate = $registration['cal_event_getdate'];
						if (!in_array($curEventUid, $processedEvents)) $processedEvents[] = $curEventUid;
					}
					$this->rendering->setRegistration($registration);
					
						//get the user
					$select = 'fe_users.*';
					$table = 'fe_users';
					$where = 'fe_users.uid=' . $registration['feuser_uid'] . $this->cObj->enableFields('fe_users');
					$orderBy = '';
					$userRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($userRes) == 0) continue;
					if (!($user = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($userRes))) continue;
					$this->rendering->setUser($user);
					
						//Render the registration entry
					$items .= $this->rendering->renderForm($config, 'show', 'ITEMS');
				}
				
					//Render the last event and add it to the event array
				if ($curEventUid != 0)
					$eventList[$curEventGetdate . $curEventUid] = 
						$this->rendering->renderForm($config, 'show', 'EVENTENTRY') . 
							($items=='' ? $noItems : $items);
			}
			
				//now get the events without registration
			$processedEventsList = (count($processedEvents)==0) ? '0' : implode(', ', $processedEvents);
			$select = 'tx_cal_event.*, tx_cal_organizer.tx_register4cal_feUserId';
			$table = 'tx_cal_event, tx_cal_organizer';
			$where = ' tx_cal_event.organizer_id = tx_cal_organizer.uid AND' .			/* Join event and organizer */
				 ' tx_cal_event.uid NOT IN (' . $processedEventsList . ') AND' .		/* Select events */ 
				 ' tx_cal_event.start_date>=' . date('Ymd') . ' AND' .				/* Event in the future */
				 ' tx_cal_event.tx_register4cal_activate = 1' .					/* Registration activated */
				 $this->cObj->enableFields('tx_cal_event');					/* Take sysfields into account */
			$orderBy = 'start_date ASC';
			$eventRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, '', $orderBy);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($eventRes) != 0) {
				$this->rendering->unsetUser();
				$this->rendering->unsetRegistration();
				while (($event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($eventRes))) {
					if (!$isAdminUser && !in_Array($feUserId,explode(',', $event['tx_register4cal_feUserId']))) continue;
					$this->rendering->setEvent($event);
					$eventList[$event['start_date'] . $event['uid']] = 
						$this->rendering->renderForm($config, 'show', 'EVENTENTRY') .
							$noitems;
				}
			}
			if (!isset($eventList)) $eventList=$this->rendering->renderForm($config, 'show', 'NOEVENTS');
		} else {
			$eventList=$this->rendering->renderForm($config, 'show', 'NOLOGIN');
		}
			//Final rendering (sort the items before)
		if (is_array($eventList)) ksort($eventList);
		$presetSubparts = Array();
		$presetSubparts['###NOLOGIN###'] = '';
		$presetSubparts['###NOEVENTS###'] = '';
		$presetSubparts['###ITEMS###'] = '';
		$presetSubparts['###NOITEMS###'] = '';
		$presetSubparts['###EVENTENTRY###'] = is_array($eventList) ? implode($eventList) : $eventList;
		$content .= $this->rendering->renderForm($config, 'show', '', $presetSubparts);
		
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
	 * @param	string		$pidlist: List of pids from which registrations should be read
         *
         * @return 	string		HTML for event list
         */
	function eventList($pidlist) {
		$feUserId = $GLOBALS['TSFE']->fe_user->user['uid'];
		if ($feUserId != 0) {
			$isAdminUser = in_Array($feUserId, $this->settings['adminusers']);
			$config = 'listOutput.events';
				//user is the current user in this case
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
			
				//get registrations
			$select = 'tx_register4cal_registrations.*';
			$table = 'tx_register4cal_registrations';
			$where = 'tx_register4cal_registrations.feuser_uid=' . intval($feUserId) . ' AND' .
				 ' tx_register4cal_registrations.cal_event_getdate>=' . date('Ymd') . ' AND' .
				 ' tx_register4cal_registrations.pid IN (' . $pidlist . ') AND' .
				 ' tx_register4cal_registrations.status <> 3' .
				 $this->cObj->enableFields('tx_register4cal_registrations');
			$orderBy = 'tx_register4cal_registrations.cal_event_getdate ASC';
			$resRegistration = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy, $limit);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($resRegistration) != 0) {
				while (($rowRegistration = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resRegistration))) {
					$this->rendering->setRegistration($rowRegistration);
					
						//get the event and render list
					$select = 'tx_cal_event.*';
					$table = 'tx_cal_event';
					$where = 'tx_cal_event.uid = ' . $rowRegistration['cal_event_uid'] . ' AND ' .
						 ' (tx_cal_event.start_date=' . $rowRegistration['cal_event_getdate'] . ' OR' .    	/* Either registration for the event */
						 '  tx_cal_event.freq <> \'none\')' .                                    	    	/* or recurring event */
						 $this->cObj->enableFields('tx_cal_event');
					$orderBy = '';
					$resEvent = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy, $limit);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($resEvent) != 0) {
						while (($rowEvent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resEvent))) {
							$this->rendering->setEvent($rowEvent);
							$items.=$this->rendering->renderForm($config, 'show', 'ITEMS');
						}
					}
				}
			}
			$nologin = '';
		} else {
			$items = '';
			$nologin = $this->rendering->renderForm($config, 'eventList', 'show', 'NOLOGIN');
		}
			//Final rendering
		if ($items == '' && $nologin == '') $noitems=$this->rendering->renderForm($config, 'show', 'NOITEMS');
		$PresetSubparts = Array();
		$PresetSubparts['###ITEMS###'] = $items;
		$PresetSubparts['###NOITEMS###'] = $noitems;
		$PresetSubparts['###NOLOGIN###'] = $nologin;
		$content = $this->rendering->renderForm($config, 'show', '', $PresetSubparts);
		
		return $content;
	}

	/***********************************************************************************************************************************************************************
	*
	* Supporting functions
	*
	**********************************************************************************************************************************************************************/
	
	/*
         * Read an event record from the database
         *
	 * @param	integer		$eventUid: uid of the event to read
	 *
         * @return	array		Event record
         */
	private function readEventRecord($eventUid) {
		$select = 'tx_cal_event.*';
		$table = 'tx_cal_event';
		$where = 'tx_cal_event.uid=' . intval($eventUid);
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$event = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		
		return $event;
	}

	/*
	 * Check if registration for an event is enabled and if we are in the registration period
	 *
	 * @param	array		$event: Array containing event data
	 * @param	integer		$getDate: Getdate value (for recurring events)
	 *
	 * return	boolean: TRUE: registration is enabled, FALSE: registration is disabled
	 */
	private function isRegistrationEnabled($event, $getDate) {
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
		
		return $regEnabled;
	}

	/*
	* Check if user has alreay registered for the event and store the registration record if this is the case
	*
	* @param	integer		$eventUid: UID of the event for which the registration should be checked
	* @param	integer		$eventGetDate: getDate of the event for which the registration should be checked
	* @param	integer		$userUid: UID if the user for which the registration should be checked
	* @param	integer		$eventPid: PID of the page containing the registrations
	* @param	integer		$status: Returns the registration status
	* @return  	boolean  	TRUE: User has already registered, FALSE, User has not yet registered
	*/
	public function isUserAlreadyRegistered($eventUid, $eventGetDate, $userUid, $eventPid, &$status) {
		$select = '*';
		$table = 'tx_register4cal_registrations';
		$where = 'cal_event_uid=' . intval($eventUid) .
			 ' AND cal_event_getdate=' . intval($eventGetDate) .
			 ' AND feuser_uid=' . intval($userUid) .
			 ' AND pid=' . intval($eventPid) .
			 ' AND status <> 3'.
			 $this->cObj->enableFields('tx_register4cal_registrations');
		$orderBy = 'tstamp desc';
		$groupBy = '';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy , $orderBy, $limit);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
			$alreadyReg = false;
		} else {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$this->rendering->setRegistration($row);
			$status = $row['status'];
			$alreadyReg = true;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		return $alreadyReg;
	}

	/*
	* Determine the possible registration status
	*
	* @param	integer		$eventUid: Uid if the event (not mandatory from event record!)
	* @param	integer		$eventGetDate: Date of the event (not mandatory from event record!)
	* @param	integer		$event: event record
	* @return  	integer  	Possible registration status as following:
	*					0: No registration possible
	*					1: Normal registration possible
	*					2: Waitlist enlisting possible
	*/
	public function getPossibleRegistrationStatus($eventUid, $eventGetDate, $event) {
		$statusCount = tx_register4cal_user1::getRegistrationCount($eventUid, $eventGetDate, $event['pid']);
		
		if ($event['tx_register4cal_maxattendees'] == 0) {
			$regStatus = 1;								// no max # of attendees --> registration always possible
		} else {
			if ($event['tx_register4cal_maxattendees'] > $statusCount[1])	{	// max # of attendees given, but not reached --> registration possible
				$regStatus = 1;
			} else {								// max # of attendees given and reached --> check waitlist
				if ($event['tx_register4cal_waitlist'] == 1) {
					$regStatus = 2;						// Waitlist enabled --> Waitlist
				} else {
					$regStatus = 0;						// Waitlist disabled --> Registration not possible
				}
			}
		}
		return $regStatus;
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
	* @param	array		$event: Event for which the registration should be stored
	* @param	integer		$status: Returns the status for the registration
	*
	* @return  boolean  TRUE: Registration sucessfully stored,  FALSE: Registration not stored
	*/
	private function storeData($data, $event, &$status) {
		if (!$this->isUserAlreadyRegistered($data['uid'], $data['getdate'], $GLOBALS['TSFE']->fe_user->user['uid'], $event['pid'], $status)) {	
				// Check possible registration status
			$status = $this->getPossibleRegistrationStatus($data['uid'], $data['getdate'], $event);
			if ($status != 0) {
					//Prepare additional fields
				$addfields = Array();
				$userfields = $this->settings['userfields'];
				if (is_array($userfields)) {
					foreach ($userfields as $field) {
						$addfield = Array();
						$addfield['conf'] = $field;
						$addfield['value'] = $this->piVars['FIELD_' . $field['name']];
						$addfields[$field['name']] = $addfield;
					}
				}
				 
					//write registration record
				$recordlabel = tx_register4cal_user1::formatDate($data['getdate'], 0, $this->settings['date_format']) . ' ' . $event['title'] . ': ' . $GLOBALS['TSFE']->fe_user->user['name'];
				$write = Array();
				$write['pid'] = intval($event['pid']);
				$write['tstamp'] = time();
				$write['crdate'] = time();
				$write['recordlabel'] = $recordlabel;
				$write['cruser_id'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
				$write['cal_event_uid'] = intval($data['uid']);
				$write['cal_event_getdate'] = intval($data['getdate']);
				$write['feuser_uid'] = intval($GLOBALS['TSFE']->fe_user->user['uid']);
				$write['additional_data'] = serialize($addfields);
				$write['status'] = $status;
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_register4cal_registrations', $write);
				 
				$this->rendering->setRegistration($write);
				$success = TRUE;
			} else {
				$success = FALSE;
			}
		} else {
			$success = FALSE;
		}
		return $success;
	}

	/*
	* Store the cancelation of registration in the database
	*
	* @param	array		$data: Data for the registration
	* @param	array		$event: Event for which the registration should be stored
	* @param	integer		$oldStatus: Returns the status of the registration prior to the cancellation
	*
	* @return  	boolean  	TRUE: Unregistration sucessfully stored,  FALSE: Unregistration not stored
	*/
	private function storeDataUnregister($data, $event, &$oldStatus) {
		if ($this->isUserAlreadyRegistered($data['uid'], $data['getdate'], $GLOBALS['TSFE']->fe_user->user['uid'], $event['pid'], $oldStatus)) {	
			$update = Array();
			if ($this->settings['keepUnregistered'] == 1) {
				$update['status'] = 3;
			} else {
				$update['deleted'] = 1;
			}
			$update['tstamp'] = time();
			
			$where = 'cal_event_uid=' . intval($data['uid']) .
			 ' AND cal_event_getdate=' . intval($data['getdate']) .
			 ' AND feuser_uid=' . intval($GLOBALS['TSFE']->fe_user->user['uid']) .
			 ' AND pid=' . intval($event['pid']) .
			 $this->cObj->enableFields('tx_register4cal_registrations');
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_register4cal_registrations', $where, $update);
				
			$this->rendering->unsetRegistration();

			$success = TRUE;
		} else {
			$success = FALSE;
		}
		return $success;
	}

	/*
	* Check if there are free places and waitlist entries. If there are, the free places will be filled from
	* the oldest waitlist entries. Confirmation and notification emails will be sent
	*
	* @param	integer		$eventGetDate: Date of the event (not mandatory the same than in the event record!)
	* @param	array		$event: Event for which the registration should be checked
	*
	* @return  	void
	*/
	private function checkWaitlist($eventGetDate, $event) {
			// We only need to do this if the number if attendees is limited
		if ($event['tx_register4cal_maxattendees'] > 0 && $this->isRegistrationEnabled($event, $eventGetDate)) {
			$statusCount = tx_register4cal_user1::getRegistrationCount($event['uid'], $eventGetDate, $event['pid']);
				// as long we have some free places and somebody on the waiting list
			while ($event['tx_register4cal_maxattendees'] > $statusCount[1] && $statusCount[2] > 0) {
				// get oldest entry from waiting list
				
				$select = '*';
				$table = 'tx_register4cal_registrations';
				$where = 'cal_event_uid=' . intval($event['uid']) .
					' AND cal_event_getdate=' . intval($eventGetDate) .
					' AND pid=' . intval($event['pid']) .
					' AND status = 2' .
					$this->cObj->enableFields('tx_register4cal_registrations');
				$orderBy = 'tstamp';
				$groupBy = '';
				$limit = '1';
				$rowsRegistration = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select, $table, $where, $groupBy , $orderBy, $limit);
				$rowRegistration = $rowsRegistration[0];
				
					// set this entry to "attending"
				$update = Array();
				$update['status'] = 1;
				$update['tstamp'] = time();
				$where = 'uid='.intval($rowRegistration['uid']);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_register4cal_registrations', $where, $update);
				
					// update registration array and set in rendering class
				$rowRegistration['status'] = $update['status'];
				$rowRegistration['tstamp']= $update['tstamp'];
				$this->rendering->setRegistration($rowRegistration);
				
					// set user who can now be set on "attending"
				$select = '*';
				$table = 'fe_users';
				$where = 'uid='.intval($rowRegistration['feuser_uid']) . $this->cObj->enableFields('fe_users');
				$groupBy = '';
				$orderBy = '';
				$limit = '';
				$rowsUser = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select, $table, $where, $groupBy , $orderBy, $limit);
				$rowUser = $rowsUser[0];
				$this->rendering->setUser($rowUser);
				
					//send notification and confirmation email
				$notificationSent = $this->sendNotificationMail(1, 3);
				$confirmationSent = $this->sendConfirmationMail(1, 3);
				
					//recount status
				$statusCount = tx_register4cal_user1::getRegistrationCount($event['uid'], $eventGetDate, $event['pid']);
			}
			
				// clear registration and reset user
			$this->rendering->setUser($GLOBALS['TSFE']->fe_user->user);
			$this->rendering->unsetRegistration();
		}
	}
	
	/*
	* Render the registration form
	*
	* @return  string  	Registration form
	*/
	private function renderRegistrationForm($event) {
		$regStatus = $this->getPossibleRegistrationStatus($event['uid'], $event['start_date'], $event);
		switch ($regStatus) {
			case 0:
				// Registration not possible
				$content = $this->rendering->renderForm('single.noregister', 'show');
				break;
			case 1:
				// Registration active
				// Falltrough
			case 3:
				// Reregistration active
				$content = $this->rendering->renderForm('single.registration.enter', 'edit');
				break;
			case 2:
				// Waitlist registration active
				$content = $this->rendering->renderForm('single.waitlist.enter', 'edit');
				break;
		}
				
		return $content;
	}
	 
	/*
	* Render the registration confirmation
	*
	* @return  string  	Registration confirmation
	*/
	private function renderRegistrationConfirmation($status) {
		switch ($status) {
			case 1:
				$content = $this->rendering->renderForm('single.registration.confirmation', 'show');
				break;
			case 2:
				$content = $this->rendering->renderForm('single.waitlist.confirmation', 'show');
				break;
		}	
		
		return $content;
	}
	 
	/*
	* Render the registration details
	*
	* @return  string  	Registration details
	*/
	private function renderRegistrationDetails($status) {
		switch ($status) {
			case 1:	
				$content = $this->rendering->renderForm('single.registration.alreadyDone', 'show');
				break;
			case 2:
				$content = $this->rendering->renderForm('single.waitlist.alreadyDone', 'show');
				break;
		}
		
		return $content;
			
	}


	/*
	 * Render the "need to login" form
	 *
	 * @return   string  	"Need to login" form
	 */
	private function renderNeedLoginForm() {
		return $this->rendering->renderForm('single.needLogin', 'show');
	}
	
	/*
	* Render the registration form
	*
	* @return  string  	Registration form
	*/
	function renderListRegistrationSubmit() {
		return $this->rendering->renderForm('list.submit', 'edit');
	}
	 
	/*
	* Render the registration form
	*
	* @param   array	$event: Event data
	* @return  string  	Registration form
	*/
	function renderListRegistrationEvent($event) {
		$regStatus = $this->getPossibleRegistrationStatus($event['uid'], $event['start_date'], $event);
		switch ($regStatus) {
			case 0:
				// Registration not possible
				break;
			case 1:
				// Registration active
				$content = $this->rendering->renderForm('list.registration.enter', 'edit');
				break;
			case 2:
				// Waitlist registration active
				$content = $this->rendering->renderForm('list.waitlist.enter', 'edit');
				break;
		}
	
		return $content;
	}	 
	 
	/*
	* Render the registration details
	*
	* @return  string  	Registration details
	*/
	function renderListRegistrationDetails($status) {
		switch ($status) {
			case 1:
				$content = $this->rendering->renderForm('list.registration.alreadydone', 'show');
				break;
			case 2:
				$content = $this->rendering->renderForm('list.waitlist.alreadydone', 'show');
				break;
		}
		
		return $content;
	}	
	
	/*
	* Send confirmation email to the user (if wanted and email address of user is available)
	*
	* @param	integer		$status: Status of the registration (1: registered, 2: waitlist)
	* @param	integer		$action: Action: (1: registering, 2: unregistering, 3: waitlist->registered)
	* @return  	boolean  	TRUE: email sent,  FALSE: email not sent
	*/
	private function sendConfirmationMail($status, $action) {
			//Send email if it should be sent and we have the email of the fe-user
		$mailconf = $this->settings['mailconf'];
		if ($mailconf['sendConfirmationMail'] == 1 && $GLOBALS['TSFE']->fe_user->user['email']) {
				//render email
			switch ($status.'-'.$action) {
				case '1-1':
					$content = $this->rendering->renderForm('email.registration.enter.confirmation', 'show');
					$subject = $this->rendering->renderSubject('email.registration.enter.confirmation');
					break;
				case '2-1':
					$content = $this->rendering->renderForm('email.waitlist.enter.confirmation', 'show');
					$subject = $this->rendering->renderSubject('email.waitlist.enter.confirmation');
					break;
				case '1-2':
					$content = $this->rendering->renderForm('email.registration.cancel.confirmation', 'show');
					$subject = $this->rendering->renderSubject('email.registration.cancel.confirmation');
					break;
				case '2-2':
					$content = $this->rendering->renderForm('email.waitlist.cancel.confirmation', 'show');
					$subject = $this->rendering->renderSubject('email.waitlist.cancel.confirmation');
					break;
				case '1-3':
					$content = $this->rendering->renderForm('email.waitlist.upgrade.confirmation', 'show');
					$subject = $this->rendering->renderSubject('email.waitlist.upgrade.confirmation');
					break;
			}
			 
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
	* @param	integer		$status: Status of the registration (1: registered, 2: waitlist)
	* @param	integer		$action: Action: (1: registering, 2: unregistering, 3: waitlist->registered)
	* @return  	boolean  	TRUE: email sent,  FALSE: email not sent
	*/
	private function sendNotificationMail($status, $action) {
			//Concatenate organizer email and admin email if necessary
		$mailconf = $this->settings['mailconf'];
		if ($this->settings['organizer_email'] != '' && $mailconf['adminAddress'] != '') {
			$mailTo = $this->settings['organizer_email'] . ',' . $mailconf['adminAddress'];
		} else {
			$mailTo = $this->settings['organizer_email'] . $mailconf['adminAddress'];
		}

			//Send email if it should be sent and we have at least one email adress given
		if ($mailconf['sendNotificationMail'] == 1 && ($mailTo != '')) {
				//render email
			switch ($status.'-'.$action) {
				case '1-1':
					$content = $this->rendering->renderForm('email.registration.enter.notification', 'show');
					$subject = $this->rendering->renderSubject('email.registration.enter.notification');
					break;
				case '2-1':
					$content = $this->rendering->renderForm('email.waitlist.enter.notification', 'show');
					$subject = $this->rendering->renderSubject('email.waitlist.enter.notification');
					break;
				case '1-2':
					$content = $this->rendering->renderForm('email.registration.cancel.notification', 'show');
					$subject = $this->rendering->renderSubject('email.registration.cancel.notification');
					break;
				case '2-2':
					$content = $this->rendering->renderForm('email.waitlist.cancel.notification', 'show');
					$subject = $this->rendering->renderSubject('email.waitlist.cancel.notification');
					break;		
				case '1-3':
					$content = $this->rendering->renderForm('email.waitlist.upgrade.notification', 'show');
					$subject = $this->rendering->renderSubject('email.waitlist.upgrade.notification');
					break;					
			}
			 
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