<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Thomas Ernst <typo3@thernst.de>
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
 * ************************************************************* */
/**
 * class.tx_register4cal_registration_model.php
 *
 * Class implementing a registration model
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('cal') . 'res/pearLoader.php');
require_once(t3lib_extMgm::extPath('cal') . 'model/class.tx_cal_phpicalendar_model.php');
require_once(t3lib_extMgm::extPath('cal') . 'controller/class.tx_cal_registry.php');

/**
 * Class to implement a registration model
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_registration_model {
    /* =========================================================================
     * Private variables
     * ========================================================================= */

    /**
     * User who registered (data from fe_users)
     * @var Array
     */
    private $user = Array();

    /**
     * Event for which the user registered (data from tx_cal_events, extendend by own fields)
     * @var Array
     */
    private $event = Array();

    /**
     * Date of event (required for recurring events, specifying the individual event)
     * @var integer
     */
    private $eventDate = 0;

    /**
     * Registration record (data from tx_register4cal_registrations)
     * @var Array
     */
    private $registration = Array();

    /**
     * Location record linked to the event
     * @var array
     */
    private $location = Array();

    /**
     * Organizer record linked to the event
     * @var array
     */
    private $organizer = Array();

    /**
     * Status of registration
     * 0  registration not activated
     * 1  no registration possible at the moment (outside registration period)
     * 2  no registration possible at the moment (event fully booked)
     * 3  normal registration is possible
     * 4  waitlist registration possible
     * 5  user has already registered
     * 6  user has already enlisted on waitlist
     * @var integer
     */
    private $status = 0;  /// Status of registration
    /**
     * Extracted additional registration data (userfields)
     * @var Array
     */
    private $userdefinedFields = Array();  /// Userdefined fields for registration
    /**
     * Name of the userfield containing the number of participants
     * @var string
     */
    private $numberOfAttendeesUserfield = '';

    /**
     * Flag: Registration is visible for other users
     * @var type Boolean
     */
    private $visibleForOtherUsers = 0;

    /**
     * Instance of tx_register4cal_settings model, containing extension settings
     * @var tx_register4cal_settings
     */
    private $settings;

    /* =========================================================================
     * Properties (get and set methods)
     * ========================================================================= */

    /**
     * Set the user
     * @param integer $userId Id of user to set
     */
    public function setUser($userId) {
        $this->user = $this->readUserRecord($userId);
    }

    /**
     * Get an user field
     * @var string $field Name of field to retrieve.
     * @return mixed Empty string if field does not exist, otherwise value
     */
    public function getUserField($field) {
        if (isset($this->user[$field]))
            return $this->user[$field];
        else
            return '';
    }

    /**
     * Get an event field
     * @param string $field Name of field to retrieve
     * @return mixed Emptry string if field does not exist, otherwise value 
     */
    public function getEventField($field) {
        if (isset($this->event[$field]))
            return $this->event[$field];
        else
            return '';
    }

    /**
     * Get the event data (identify a single event from a set of recurring events)
     * @return integer
     */
    public function getEventDate() {
        return $this->eventDate;
    }

    /**
     * Get an event location field
     * @param string $field Name of field to retrieve
     * @return mixed Emptry string if field does not exist, otherwise value
     */
    public function getLocationField($field) {
        if (isset($this->location[$field]))
            return $this->location[$field];
        else
            return '';
    }

    /**
     * Get an event organizer field
     * @param string $field Name of field to retrieve
     * @return mixed Emptry string if field does not exist, otherwise value
     */
    public function getOrganizerField($field) {
        if (isset($this->organizer[$field]))
            return $this->organizer[$field];
        else
            return '';
    }

    /**
     * Get the current registration status
     * @see $this->status for possible values
     * @return integer Current registration status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Get an registration field
     * @param string $field Name of field to retrieve
     * @return mixed Emptry string if field does not exist, otherwise value
     */
    public function getRegistrationField($field) {
        if (isset($this->registration[$field]))
            return $this->registration[$field];
        else
            return '';
    }

    /**
     * Get the array with the userdefined fields
     * @return array Userdefined fields
     */
    public function getUserdefinedFields() {
        return $this->userdefinedFields;
    }

    /**
     * Get a single userdefined field
     * @param string $field Name of field to retrieve
     * @return boolean/array FALSE if field does not exist, otherwise Array('conf'=>config, 'value'=>value);
     */
    public function getUserdefinedField($field) {
        if (isset($this->userdefinedFields[$field]))
            return $this->userdefinedFields[$field];
        else
            return FALSE;
    }

    /**
     * Sets the value of an userdefined field
     * @param string $field Name of field to set
     * @param integer $value Value to set
     */
    public function setUserdefinedFieldValue($field, $value) {
        if (!isset($this->userdefinedFields[$field]))
            throw new Exception('Unknown userdefined field "' . $field . '"!');
        $this->userdefinedFields[$field]['value'] = $value;
    }

    /**
     * Returns if the registration should be visible for other users
     * @return integer 1: Should be visible, 0: Should not be visible
     */
    public function getVisibleForOtherUsers() {
        return ($this->visibleForOtherUsers) ? 1 : 0;
    }

    /**
     * Sets if the registration should be visible for other users
     * @param integer $value 1: Should be visible, 0: Should not be visible 
     */
    public function setVisibleForOtherUsers($value) {
        $this->visibleForOtherUsers = ($value) ? 1 : 0;
    }

    /* =========================================================================
     * Constructor and static getInstance method
     * ========================================================================= */

    /**
     * Create an instance of the class while taking care of the different ways
     * to instanciace classes having constructors with parameters in different
     * Typo3 versions
     * Class constructor
     * @param integer $eventId Id of the event
     * @param integer $eventDate Event data of the event (for recurring events)
     * @param integer $userId Id of the user (optional)
     * @param integer $registrationId Id of the registration (optional)
     * @return tx_register4cal_registration_model
     */
    public static function getInstance($eventId = 0, $eventDate = 0, $userId = 0, $registrationId = 0) {
        $className = 'tx_register4cal_registration_model';
        if (tx_register4cal_static::getTypo3IntVersion() <= 4003000) {
            $className = &t3lib_div::makeInstanceClassName($className);
            $class = new $className($eventId, $eventDate, $userId, $registrationID);
        } else {
            $class = &t3lib_div::makeInstance($className, $eventId, $eventDate, $userId, $registrationId);
        }
        return $class;
    }

    /**
     * Class constructor
     * @param integer $eventId Id of the event
     * @param integer $eventDate Event data of the event (for recurring events)
     * @param integer $userId Id of the user (optional)
     * @param integer $registrationId Id of the registration (optional)
     */
    public function __construct($eventId = 0, $eventDate = 0, $userId = 0, $registrationId = 0) {
        if ($registrationId != 0) {
            // registrationId given, read registration data
            $this->registration = $this->readRegistrationRecord($registrationId);
            if (count($this->registration) == 0)
                throw new Exception('Unknown Registration "' . $registrationId . '"!');

            $eventId = $this->registration['cal_event_uid'];
            $eventDate = $this->registration['cal_event_getdate'];
            $userId = $this->registration['feuser_uid'];

            $this->visibleForOtherUsers = $this->registration['visible_for_other_users'];
        }
        if (intval($eventId) == 0)
            throw new Exception('Missing EventId!');
        if (intval($eventDate) == 0)
            throw new Exception('Missing EventDate!');

        // get settings
        require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_settings.php');
        $this->settings = tx_register4cal_settings::getInstance();

        // set event data
        $this->event = $this->readEventRecord($eventId, $eventDate);
        $this->eventDate = $eventDate;
        $this->readNumberOfRegistrations();

        // set user data
        $this->user = $this->readUserRecord($userId);

        // set location data
        $this->location = $this->readLocationRecord();

        // set organizer data
        $this->organizer = $this->readOrganizerRecord();

        // set registration data (if not already read)
        if (count($this->registration) == 0)
            $this->registration = $this->readRegistrationRecord();
        $this->visibleForOtherUsers = $this->registration['visible_for_other_users'];

        // set userfield data
        $this->userdefinedFields = $this->readUserfieldRecords();
        $this->numberOfAttendeesUserfield = $this->findNumberOfAttendeesUserfield();

        // determine status
        $this->status = $this->determineStatus();
    }

    /* =========================================================================
     * Static public methods
     * ========================================================================= */

    /**
     * Provide a list of registrations for the current fe_user
     * @global tslib_fe $TSFE
     * @global t3lib_DB $TYPO3_DB
     * @param string $pidlist List of pid's to check for registrations
     * @return Array Array of tx_register4cal_registration_model objects
     */
    public static function getRegistrationsForUser($pidlist) {
        global $TSFE, $TYPO3_DB;

        $feUserId = $TSFE->fe_user->user['uid'];
        $select = 'cal_event_uid, cal_event_getdate, feuser_uid';
        $table = 'tx_register4cal_registrations';
        $where = 'tx_register4cal_registrations.feuser_uid=' . intval($feUserId) . ' AND' .
                ' tx_register4cal_registrations.cal_event_getdate>=' . date('Ymd') . ' AND' .
                ' tx_register4cal_registrations.pid IN (' . $pidlist . ') AND' .
                ' tx_register4cal_registrations.status <> 3' .
                $TSFE->cObj->enableFields('tx_register4cal_registrations');
        $orderBy = 'tx_register4cal_registrations.cal_event_getdate ASC';
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy, $limit);
        $registrations = Array();
        while (($row = $TYPO3_DB->sql_fetch_assoc($result))) {
            $registrations[] = tx_register4cal_registration_model::getInstance($row['cal_event_uid'], $row['cal_event_getdate'], $row['feuser_uid']);
        }
        $TYPO3_DB->sql_free_result($result);
        return $registrations;
    }

    /**
     * Provide a list of registrations for events, for which the current fe_user
     * is the organizer (or all registrations if current fe_user is in the list
     * of register4cal-admins)
     * @global tslib_fe $TSFE
     * @global t3lib_DB $TYPO3_DB
     * @param string $pidlist List of pid's to check for registrations
     * @param tx_register4cal_settings Settings
     * @return Array Array of tx_register4cal_registration_model objects:
     * 				Array(eventDate=>Array(eventId=>Array(tx_register4cal_registration_model))
     */
    public static function getRegistrationsForOrganizer($pidlist) {
        global $TSFE, $TYPO3_DB;

        $settings = tx_register4cal_settings::getInstance();
        
        //select events with registrations
        $processedEventArray = Array();
        $select = 'uid';
        $table = 'tx_register4cal_registrations';
        $where = ' tx_register4cal_registrations.cal_event_getdate>=' . date('Ymd') .
                ' AND tx_register4cal_registrations.pid IN (' . $pidlist . ') ' .
                $TSFE->cObj->enableFields('tx_register4cal_registrations');
        $orderBy = 'cal_event_getdate ASC, cal_event_uid ASC, status ASC, crdate ASC';
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where, '', $orderBy);
        $registrations = Array();
        while (($row = $TYPO3_DB->sql_fetch_assoc($result))) {
            $registration = tx_register4cal_registration_model::getInstance(0, 0, 0, $row['uid']);
            $processedEventArray[] = $registration->getEventField('uid');
            if ($registration->userIsOrganizer())
                $registrations[$registration->getEventDate()][$registration->getEventField('uid')][] = $registration;
        }
        $TYPO3_DB->sql_free_result($result);


        //add events without registrations
        $processedEventList = (count($processedEventArray) == 0) ? '0' : implode(',', $processedEventArray);

        $select = 'tx_cal_event.*, ' . $settings->calOrganizerStructure . '.uid';
        $table = 'tx_cal_event, ' . $settings->calOrganizerStructure;
        $where = 'tx_cal_event.organizer_id = ' . $settings->calOrganizerStructure . '.uid' .
                ' AND tx_cal_event.uid NOT IN (' . $processedEventList . ') AND' . // Select events 
                ' tx_cal_event.start_date>=' . date('Ymd') . ' AND' . // Event in the future
                ' tx_cal_event.tx_register4cal_activate = 1' . // Registration activated
                $TSFE->cObj->enableFields('tx_cal_event') . // Take sysfields into account
                $TSFE->cObj->enableFields($settings->calOrganizerStructure);
        $orderBy = 'start_date ASC';

        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where, '', $orderBy);
        while (($row = $TYPO3_DB->sql_fetch_assoc($result))) {
            $registration = tx_register4cal_registration_model::getInstance($row['uid'], $row['start_date']);
            if ($registration->userIsOrganizer())
                $registrations[$row['start_date']][$row['uid']][] = $registration;
        }
        $TYPO3_DB->sql_free_result($result);

        return $registrations;
    }

    /**
     * Provide a list of eventUids and eventDates for events, having waitlist
     * entries. eventId and eventDate can be limited
     * @global t3lib_DB $TYPO3_DB
     * @param string $pidlist List of pid's to take into account for searching
     * @param integer $eventId Limit eventId to this event
     * @param integer $eventDate Limit eventDate to this date
     * @return array Array of eventUids and eventDates Array(Array('cal_event_uid'=>eventId, 'cal_event_getdate'=>eventDate))
     */
    public static function getEventsWithWaitlistEntries($eventId = 0, $eventDate = 0) {
        global $TYPO3_DB, $TSFE;

        $select = 'DISTINCT cal_event_uid, cal_event_getdate';
        $table = 'tx_register4cal_registrations';
        $where = 'status=2' .
                $TSFE->cObj->enableFields($table);
        if ($eventId)
            $where .= ' AND cal_event_uid=' . intval($eventId);
        if ($eventDate)
            $where .= ' AND cal_event_getdate=' . intval($eventDate);
        $events = $TYPO3_DB->exec_SELECTgetRows($select, $table, $where);
        return $events;
    }

    /**
     * Provide a list of eventUids and eventDates for events, having cancelled
     * entries. eventId and eventDate can be limited
     * @global t3lib_DB $TYPO3_DB
     * @param string $pidlist List of pid's to take into account for searching
     * @param integer $eventId Limit eventId to this event
     * @param integer $eventDate Limit eventDate to this date
     * @return array Array of eventUids and eventDates Array(Array('cal_event_uid'=>eventId, 'cal_event_getdate'=>eventDate))
     */
    public static function getEventsWithCancelledEntries($eventId = 0, $eventDate = 0) {
        global $TYPO3_DB, $TSFE;

        $select = 'DISTINCT cal_event_uid, cal_event_getdate';
        $table = 'tx_register4cal_registrations';
        $where = 'status=3' .
                $TSFE->cObj->enableFields($table);
        if ($eventId)
            $where .= ' AND cal_event_uid=' . intval($eventId);
        if ($eventDate)
            $where .= ' AND cal_event_getdate=' . intval($eventDate);
        $events = $TYPO3_DB->exec_SELECTgetRows($select, $table, $where);
        return $events;
    }

    /**
     * Provide a list of registrations for a given event. The status can be limited
     * @global tslib_fe $TSFE
     * @global t3lib_DB $TYPO3_DB
     * @param integer $eventId Id of the event whose registrations should be returned
     * @param integer $eventDate Date of the event whose registrations should be returned
     * @param integer $status If given: Only provide registrations with this status
     * @return Array Array of tx_register4cal_registration_model objects
     */
    public static function getRegistrationsForEvent($eventId, $eventDate, $status = 0) {
        global $TSFE, $TYPO3_DB;

        $select = 'uid';
        $table = 'tx_register4cal_registrations';
        $where = ' tx_register4cal_registrations.cal_event_uid=' . intval($eventId) . ' AND' .
                ' tx_register4cal_registrations.cal_event_getdate=' . intval($eventDate) .
                $TSFE->cObj->enableFields('tx_register4cal_registrations');
        if ($status)
            $where .= ' AND tx_register4cal_registrations.status=' . intval($status);
        $orderBy = 'crdate ASC';
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where, '', $orderBy, '');
        $registrations = Array();
        while (($row = $TYPO3_DB->sql_fetch_assoc($result))) {
            $registrations[] = tx_register4cal_registration_model::getInstance(0, 0, 0, $row['uid']);
        }
        $TYPO3_DB->sql_free_result($result);
        return $registrations;
    }

    /* =========================================================================
     * Public methods
     * ========================================================================= */

    /**
     * Register user for the event
     * @param array $messageKeys Returns array of llKeys for messages
     * @return boolean TRUE: Registered successfully, FALSE: Errors occured
     */
    public function register(&$messages) {
        // init messages
        $messages = Array();
        $hasErrors = FALSE;

        // check for free places for registration
        if ($this->numberOfAttendeesUserfield)
            $numAttendees = intval($this->userdefinedFields[$this->numberOfAttendeesUserfield]['value']);
        if (!$numAttendees)
            $numAttendees = 1;

        // check via status if registration is possible. If registration is possible, determine registration status (1=normal, 2=waitlist);
        switch ($this->status) {
            case 0:
            //fall through
            case 1:
            // fall through
            case 2:
                // fall through
                $messages[] = array('label' => 'label_error_noregistration', 'type' => 'E');
                $hasErrors = TRUE;
                break;
            case 3:
                if ($this->event['tx_register4cal_maxattendees'] == 0)
                    $registrationStatus = 1;
                else {
                    $numFree = $this->event['tx_register4cal_maxattendees'] - $this->event['tx_register4cal_numregistered'];
                    if ($numFree >= $numAttendees)
                        $registrationStatus = 1;
                    else {
                        if ($this->settings->useWaitlistIfNotEnoughPlaces) {
                            $messages[] = Array('label' => 'error_notenoughplaces_waitlist', 'type' => 'I');
                            $registrationStatus = 2;
                        } else {
                            $messages[] = Array('label' => 'error_notenoughplaces', 'type' => 'E');
                            $hasErrors = TRUE;
                        }
                    }
                }
                break;
            case 4:
                $registrationStatus = 2;
                break;
            case 5:
                $messages[] = array('label' => 'label_error_alreadyregistered', 'type' => 'E');
                $hasErrors = TRUE;
                break;
            case 6:
                $messages[] = array('label' => 'label_error_alreadywaitlist', 'type' => 'E');
                $hasErrors = TRUE;
                break;
        }

        if (!$hasErrors) {
            // set data
            if (!$this->status == 3 && !$this->status == 4)
                throw new Exception('Fatal Error: Wrong call to store registration! (0x02');
            if ($registrationStatus == 2 && $this->settings->disableWaitlist == 1)
                throw new Exception('Fatal Error: Wrong call to store registration! (0x04');
            $this->registration = Array();
            $this->registration['pid'] = $this->event['pid'];
            $this->registration['tstamp'] = time();
            $this->registration['crdate'] = time();
            $this->registration['cruser_id'] = $TSFE->fe_user->user['uid'];
            $this->registration['deleted'] = 0;
            $this->registration['recordlabel'] = tx_register4cal_datetime::formatDate($this->eventDate, 0, $this->settings->dateFormat) . ' ' . $this->event['title'] . ': ' . $this->user['name'];
            $this->registration['cal_event_uid'] = $this->event['uid'];
            $this->registration['cal_event_getdate'] = $this->eventDate;
            $this->registration['feuser_uid'] = $this->user['uid'];
            $this->registration['additional_data'] = serialize($this->userdefinedFields);
            $this->registration['status'] = $registrationStatus;
            $this->registration['numattendees'] = $numAttendees;
            $this->registration['visible_for_other_users'] = $this->visibleForOtherUsers;

            // write to db and refresh status
            $this->writeNewRegistrationRecord();
            $this->refreshStatus();

            //TODO SEV9 Version 0.7.1: we could add an "success" message to the messages-Array here

            $return = TRUE;
        } else
            $return = FALSE;
        return $return;
    }

    /**
     * Unregister user from the event
     */
    public function unregister() {
        if (!$this->status == 5 && !$this->status == 6)
            throw new Exception('Fatal Error: Wrong call to store registration! (0x03');
        if ($this->settings->disableUnregister != 0)
            throw new Exception('Fatal Error: Wrong call to store registration! (0x05)');
        if ($this->settings->keepUnregisteredEntries == 1 || $this->settings->keepUnregisteredEntries == 2) {
            $this->registration['status'] = 3;
            $this->writeUpdatedRegistrationRecord();
        } else {
            $this->deleteRegistrationRecord();
        }
        $this->registration = Array();
        $this->refreshStatus();
    }

    /**
     * Delete the registration (only possible if status = 3)
     */
    public function delete() {
        if ($this->registration['status'] == 3 && $this->userIsOrganizer()) {
            $this->deleteRegistrationRecord();
            $this->registration = Array();
        }
    }

    /**
     * check if the current fe-user is organizer of the event
     */
    public function userIsOrganizer() {

        global $TSFE;

        $feUserId = intval($TSFE->fe_user->user['uid']);

        // is user in adminUsers list?
        if (t3lib_div::inList($this->settings->adminUsers, $feUserId))
            return true;

        switch ($this->settings->calOrganizerStructure) {
            case 'tt_address':
                // Not yet implemented
                break;
            case 'tx_partner_main':
                // Not yet implemented
                break;
            case 'fe_users':
                if ($this->organizer['uid'] == $feUserId)
                    return true;
                break;
            default:
                // is user selected in organizer feUserId field
                if (t3lib_div::inList($this->organizer['tx_register4cal_feUserId'], 'fe_users_' . $feUserId))
                    return true;

                // is usergroup selected in organizer feUserId field
                $feGroupIds = explode(',', $TSFE->fe_user->user['usergroup']);
                foreach ($feGroupIds as $feGroupId) {
                    if (t3lib_div::inList($this->organizer['tx_register4cal_feUserId'], 'fe_groups_' . $feGroupId))
                        return true;
                }
                break;
        }

        return false;
    }

    /**
     * Check if registration is waitlist entry. If this is the case and the event
     * has enough free places, change to normal registration.
     * @return boolean TRUE: Registration changed to normal registration, FALSE: Registration unchanged
     */
    public function waitlistCheck() {
        // reread number of registrations (may have changes in the meantime)
        $this->readNumberOfRegistrations();

        // in this cases the entry can not be transferred from waitlist to registration
        if ($this->registration['status'] != 2)
            return FALSE;
        if ($this->registration['deleted'] != 0)
            return FALSE;
        if ($this->event['tx_register4cal_maxattendees'] - $this->event['tx_register4cal_numregistered'] < $this->registration['numattendees'] && $this->registration['numattendees'] != 0)
            return FALSE;
        // Yes we can ...transfer entry from waitlist to normal registration
        $this->registration['status'] = 1;
        $this->writeUpdatedRegistrationRecord();
        $this->refreshStatus();
        return true;
    }

    /**
     * Returns an array containing the registration objects for registrations,
     * other users did for a this event. If the current user is organizer of this
     * event (or global organizer) all registrations are provided. Otherwise only
     * such registrations are provided, where the user allowed other users to see
     * his registration.
     * @global t3lib_DB $TYPO3_DB Typo3-DB
     * @global tslib_fe $TSFE Typo3 TSFE
     * @return Array Array containing instances of tx_register4cal_registration_model
     */
    public function getRegistrationsFromOtherUsers() {
        global $TYPO3_DB, $TSFE;

        // If display of other registered users is disabled, we leave her immediately
        if (!$this->settings->showOtherRegisteredUsers_Enable)
            return Array();

        // Registration Status selection
        $status = 'status=1';
        if ($this->settings->showOtherRegisteredUsers_includeWaitlist)
            $status .= ' OR status=2';
        if ($this->settings->showOtherRegisteredUsers_includeCancelled)
            $status .= ' OR status=3';
        $status = ' AND (' . $status . ')';

        $select = 'uid';
        $table = 'tx_register4cal_registrations';
        $where = 'cal_event_uid=' . $this->event['uid'] . ' AND cal_event_getdate=' . $this->event['get_date'] . $status;
        if (!$this->settings->showOtherRegisteredUsers_includeOwnRegistration)
            $where .= ' AND feuser_uid<>' . $this->user['uid'];
        if (!$this->userIsOrganizer())
            $where .= ' AND visible_for_other_users = 1';
        $where .= $TSFE->cObj->enableFields($table);
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
        $otherUserRegistrations = Array();
        while (($row = $TYPO3_DB->sql_fetch_assoc($result))) {
            $otherUserRegistrations[] = tx_register4cal_registration_model::getInstance(0, 0, 0, $row['uid']);
        }
        $TYPO3_DB->sql_free_result($result);
        return $otherUserRegistrations;
    }

    /**
     * Indicates if the current user can display the participant details as vcard
     * @global tslib_fe $TSFE
     * @return boolean
     */
    public function IsParticipantVcardAllowed() {
        global $TSFE;

        // VCards need to be activated and properly configured
        if ($this->settings->vcardParticipantEnabled != 1)
            return false;
        if ($this->settings->vcardParticipantPageTypeNum == 0)
            return false;

        // Authorized FE-User is required
        if (intval($TSFE->fe_user->user['uid']) == 0)
            return false;

        // Vcard-Display is only possible for the organizer of the event
        if (!$this->userIsOrganizer())
            return false;

        // Vcard-Display is only possible if the user has registered for the event
        if ($this->status < 5)
            return false;

        return true;
    }

    /**
     * Indicates if the current user can display the participant details as vcard
     * @global tslib_fe $TSFE
     * @return boolean
     */
    public function IsOrganizerVcardAllowed() {
        global $TSFE;

        // VCards need to be activated and properly configured
        if ($this->settings->vcardOrganizerEnabled != 1)
            return false;
        if ($this->settings->vcardOrganizerPageTypeNum == 0)
            return false;

        // Authorized FE-User is required
        if (intval($TSFE->fe_user->user['uid']) == 0)
            return false;

        // Vcard-Display is only possible if the user has registered for the event
        if ($this->status < 5)
            return false;

        return true;
    }

    /**
     * Create link to vcard
     * @global tslib_fe $TSFE
     * @param string $label Label for link
     * @return string A-tag for vcard link
     */
    public function getVcardLink($label, $type) {
        global $TSFE;
        if ($type != 'P' && $type != 'O')
            return '';
        $vars = array(
            'type' => $this->settings->vcardParticipantPageTypeNum,
            'tx_cal_controller[view]' => 'event',
            'tx_cal_controller[type]' => 'tx_cal_phpicalendar',
            'tx_cal_controller[getdate]' => $this->event['get_date'],
            'tx_cal_controller[uid]' => $this->event['uid'],
            'tx_register4cal_view[userid]' => $this->user['uid'],
            'tx_register4cal_view[type]' => $type,
        );
        return $TSFE->cObj->getTypoLink($label, $this->settings->singleEventPid, $vars);
    }

    /* =========================================================================
     * Private methods
     * ========================================================================= */

    /**
     * Redetermine the current registration status after recounting registrations
     */
    private function refreshStatus() {
        $this->readNumberOfRegistrations();
        $this->status = $this->determineStatus();
    }

    /**
     * Determine the current status of the registration
     * @see $this->status for possible values
     * @return integer Current registration status
     */
    private function determineStatus() {
        // Status 0: Registration inactive
        if ($this->event['tx_register4cal_activate'] != 1)
            return 0;

        // The User has already registered
        if ($this->registration['uid'] != 0) {
            if ($this->event['end_timestamp'] < time()) {
                // Status 11/12: User has registered and event start time is over
                if ($this->registration['status'] == 1) {
                    return 11;
                } elseif ($this->registration['status'] == 2) {
                    return 12;
                }
            } else if ($this->event['start_timestamp'] < time()) {
                // Status 9/10: User has registered and event currently running
                if ($this->registration['status'] == 1) {
                    return 9;
                } elseif ($this->registration['status'] == 2) {
                    return 10;
                }
            } else if ($this->event['tx_register4cal_regend'] < time()) {
                // Status 7/8: User has registered and registration period is over
                if ($this->registration['status'] == 1) {
                    return 7;
                } elseif ($this->registration['status'] == 2) {
                    return 8;
                }
            } else {
                // Status 5/6: User has already registered or enlisted on waitlist, registration is still open (he may unregister)
                if ($this->registration['status'] == 1) {
                    return 5;
                } elseif ($this->registration['status'] == 2) {
                    return 6;
                }
            }
        }

        // Status 1: Outside registration period
        if ($this->event['tx_register4cal_regstart'] > time() || $this->event['tx_register4cal_regend'] < time())
            return 1;

        // Status 2/4: Event fully booked, registration impossible or waitlist
        if ($this->event['tx_register4cal_maxattendees'] != 0) {
            if ($this->event['tx_register4cal_numfree'] == 0) {
                if ($this->event['tx_register4cal_waitlist'] == 1 && $this->settings->disableWaitlist == 0)
                    return 4;
                else
                    return 2;
            }
        }

        // Status 3: Normal registration possible
        return 3;
    }

    /**
     * Search userdefined fields for the field containing the number of attendees
     * @return string fieldname of "number of attendees"-field, empty string if no "number of attendees" field is defined
     */
    private function findNumberOfAttendeesUserfield() {
        $numberOfAttendeesField = '';
        foreach ($this->userdefinedFields as $name => $data) {
            if ($data['conf']['isnumparticipants']) {
                $numberOfAttendeesField = $name;
                break;
            }
        }
        return $numberOfAttendeesField;
    }

    /* =========================================================================
     * Private methods - Accessing database
     * ========================================================================= */

    /**
     * Read an event record from the database and extend it with additional values
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     * @param integer $eventId uid of the event to read
     * @param integer $eventDate date of event (for recurring events)
     * @return array Event record
     */
    private function readEventRecord($eventId, $eventDate) {
        global $TYPO3_DB, $TSFE;

        // read the event record
        $select = 'tx_cal_event.*, tx_cal_organizer.tx_register4cal_feUserId';
        $table = 'tx_cal_event LEFT JOIN tx_cal_organizer ON tx_cal_event.organizer_id = tx_cal_organizer.uid';
        $where = 'tx_cal_event.uid=' . intval($eventId) . $TSFE->cObj->enableFields('tx_cal_event');
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
        $event = $TYPO3_DB->sql_fetch_assoc($result);
        $TYPO3_DB->sql_free_result($result);

        // prepare for counting registrations
        $event['tx_register4cal_numregistered'] = 0;
        $event['tx_register4cal_numwaitlist'] = 0;
        $event['tx_register4cal_numcancelled'] = 0;
        $event['tx_register4cal_numfree'] = 0;

        // Extend event data
        $event['organizer_name'] = $event['organizerName'];
        $event['organizer_email'] = $event['organizerEmail'];
        $event['get_date'] = $eventDate;
        $parseFunc = $TSFE->tmpl->setup['lib.']['parseFunc_RTE.'];
        if (is_array($parseFunc)) {
            $event['teaser'] = $TSFE->cObj->parseFunc($event['teaser'], $parseFunc);
            $event['description'] = $TSFE->cObj->parseFunc($event['description'], $parseFunc);
        }
        $vars = array(
            'tx_cal_controller[view]' => 'event',
            'tx_cal_controller[type]' => 'tx_cal_phpicalendar',
            'tx_cal_controller[getdate]' => intval($eventDate),
            'tx_cal_controller[uid]' => intval($eventId),
        );
        $event['link'] = $TSFE->cObj->getTypoLink_URL($this->settings->singleEventPid, $vars);


        // format date and time and append to event
        if ($event['freq'] != 'none')
            $delta = strtotime($eventDate) - strtotime($event['start_date']);

        // Start and end time as timestamp
        if ($event['allday'] == 0) {
            $event['start_timestamp'] = strtotime($event['start_date']) + $event['start_time'] + $delta;
            $event['end_timestamp'] = strtotime($event['end_date']) + $event['end_time'] + $delta;
        } else {
            $event['start_timestamp'] = strtotime($event['start_date']) + $delta;
            $event['end_timestamp'] = strtotime($event['end_date']) + 86399 + $delta;
        }

        // Start and end date/time formated with given format
        $event['start_date'] = strftime($this->settings->dateFormat, $event['start_timestamp']);
        $event['end_date'] = strftime($this->settings->dateFormat, $event['end_timestamp']);
        $event['start_time'] = strftime($this->settings->timeFormat, $event['start_timestamp']);
        $event['end_time'] = strftime($this->settings->timeFormat, $event['end_timestamp']);

        // Start and end combined
        $format = $event['allday'] == 0 ? $this->settings->dateFormat . ' ' . $this->settings->timeFormat : $this->settings->dateFormat;
        $event['formated_start'] = strftime($format, $event['start_timestamp']);
        $event['formated_end'] = strftime($format, $event['end_timestamp']);
        if ($event['allday'] != 0) {
            $event['formated_start'] .= ' ###LABEL_event_allday###';
            $event['formated_end'] = '';
        }

        // Move registration periods for recurring events (same as start/end date/time), if set
        // If no start of registration period is set, registration is possible immediately
        // If no end of registration period is set, registration ends when the event starts
        if ($event['tx_register4cal_regstart'] != 0)
            $event['tx_register4cal_regstart'] += $delta;

        if ($event['tx_register4cal_regend'] == 0) {
            $event['tx_register4cal_regend'] = $event['start_timestamp'];
        } else {
            $event['tx_register4cal_regend'] += $delta;
        }

        // Formated versions of regstart and regend
        $format = $this->settings->dateFormat . ' ' . $this->settings->timeFormat;
        $event['formated_regstart'] = strftime($format, $event['tx_register4cal_regstart']);
        $event['formated_regend'] = strftime($format, $event['tx_register4cal_regend']);

        return $event;
    }

    /**
     * Read the number of registrations for the current event and write the
     * values to the event record
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     */
    private function readNumberOfRegistrations() {
        global $TYPO3_DB, $TSFE;

        $this->event['tx_register4cal_numregistered'] = 0;
        $this->event['tx_register4cal_numwaitlist'] = 0;
        $this->event['tx_register4cal_numcancelled'] = 0;
        $this->event['tx_register4cal_numfree'] = 0;

        $select = 'status, sum(numattendees) as number';
        $table = 'tx_register4cal_registrations';
        $where = 'cal_event_uid=' . intval($this->event['uid']) .
                ' AND cal_event_getdate=' . intval($this->eventDate) .
                ' AND pid=' . intval($this->event['pid']) .
                $TSFE->cObj->enableFields($table);
        $orderBy = '';
        $groupBy = 'status';
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy);
        if ($TYPO3_DB->sql_num_rows($result) != 0) {
            while (($row = $TYPO3_DB->sql_fetch_assoc($result))) {
                switch ($row['status']) {
                    case 1:
                        $this->event['tx_register4cal_numregistered'] = $row['number'];
                        break;
                    case 2:
                        $this->event['tx_register4cal_numwaitlist'] = $row['number'];
                        break;
                    case 3:
                        $this->event['tx_register4cal_numcancelled'] = $row['number'];
                        break;
                }
            }
        }
        // eventFillMode "keepRegistrationOrder": If there are already registrations in the waitlist, do not bring forward other registrations
        // /probably having less attendees). Therefore the number of free places is set to 0 in this case
        if ($this->event['tx_register4cal_numwaitlist'] == 0 || $this->settings->eventFillMode != 1) {
            $this->event['tx_register4cal_numfree'] = $this->event['tx_register4cal_maxattendees'] - $this->event['tx_register4cal_numregistered'];
        } else {
            $this->event['tx_register4cal_numfree'] = 0;
        }
    }

    /**
     * Read the location record linked in the event
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     * @return array Location record
     */
    private function readLocationRecord() {
        global $TYPO3_DB, $TSFE;

        $select = '*';
        $table = 'tx_cal_location';
        $where = 'uid=' . intval($this->event['location_id']) . $TSFE->cObj->enableFields($table);
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
        if ($TYPO3_DB->sql_num_rows($result) == 0) {
            $location = Array();
        } else {
            $location = $TYPO3_DB->sql_fetch_assoc($result);
        }
        return $location;
    }

    /**
     * Read the organizer record linked in the event
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     * @return array Organizer record
     */
    private function readOrganizerRecord() {
        global $TYPO3_DB, $TSFE;

        $select = '*';
        $table = $this->settings->calOrganizerStructure;
        $where = 'uid=' . intval($this->event['organizer_id']) . $TSFE->cObj->enableFields($table);
        $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
        if ($TYPO3_DB->sql_num_rows($result) == 0) {
            $organizer = Array();
        } else {
            $organizer = $TYPO3_DB->sql_fetch_assoc($result);
            $this->event['organizer_name'] = $organizer['name'];
            $this->event['organizer_email'] = $organizer['email'];
        }
        return $organizer;
    }

    /**
     * Read an user record (from currently logged in user or from db)
     *
     * @param integer $userId: uid of the user to read
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     * @return array User record
     */
    private function readUserRecord($userId) {
        global $TYPO3_DB, $TSFE;

        if (intval($userId) == 0) {
            // No user given -> return empty array
            $user = Array();
        } elseif (intval($userId) == $TSFE->fe_user->user['uid']) {
            // User is currently logged in -> return him
            $user = $TSFE->fe_user->user;
        } else {
            // User is somebody differend -> read database
            $select = '*';
            $table = 'fe_users';
            $where = 'fe_users.uid=' . intval($userId) . $TSFE->cObj->enableFields($table);
            $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
            if ($TYPO3_DB->sql_num_rows($result) == 0) {
                // User not found in DB -> return empty array
                $user = Array();
            } else {
                // User found -> return him
                $user = $TYPO3_DB->sql_fetch_assoc($result);
            }
            $TYPO3_DB->sql_free_result($result);
        }
        return $user;
    }

    /**
     * Read the latest registration record for this user/event
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     * @param integer $registrationId Id of registration to read
     * @return array Registration record
     */
    private function readRegistrationRecord($registrationId = 0) {
        global $TYPO3_DB, $TSFE;

        if ($registrationId != 0) {
            $select = '*';
            $table = 'tx_register4cal_registrations';
            $where = 'uid=' . intval($registrationId) .
                    $TSFE->cObj->enableFields($table);
            $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
            if ($TYPO3_DB->sql_num_rows($result) == 0) {
                $registration = Array();
            } else {
                $registration = $TYPO3_DB->sql_fetch_assoc($result);
            }
            $TYPO3_DB->sql_free_result($result);
        } elseif ($this->user['uid']) {
            $select = '*';
            $table = 'tx_register4cal_registrations';
            $where = 'cal_event_uid=' . intval($this->event['uid']) .
                    ' AND cal_event_getdate=' . intval($this->eventDate) .
                    ' AND feuser_uid=' . intval($this->user['uid']) .
                    ' AND pid=' . intval($this->event['pid']) .
                    ' AND status<>3' . //ThER090211: Ensure that this does not cause issues somewhere
                    $TSFE->cObj->enableFields($table);
            $orderBy = 'tstamp desc';
            $result = $TYPO3_DB->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy);
            if ($TYPO3_DB->sql_num_rows($result) == 0) {
                $registration = Array();
            } else {
                $registration = $TYPO3_DB->sql_fetch_assoc($result);
            }
            $TYPO3_DB->sql_free_result($result);
        } else {
            $registration = Array();
        }
        return $registration;
    }

    /**
     * Read userfield records based on fieldset selection of event
     * @global t3lib_DB $TYPO3_DB
     * @global tslib_fe $TSFE
     * @return array Userfields (Array(fieldname=>Array('conf'=>config, 'value'=>FALSE))
     */
    private function readUserfieldRecords() {
        global $TYPO3_DB, $TSFE;
        /* Hint: tx_register4cal_fieldset values:
         * -2: Do not use a fieldset
         * -1: Use the default fieldset
         *  0: No value set, use default fieldset, too
         * >0: uid of fieldset to use
         */
        if ($this->registration['uid']) {
            $userfields = unserialize($this->registration['additional_data']);
        } else {
            $userfields = Array();
            // determine fields only if registration is active
            if ($this->event['tx_register4cal_activate'] == 1 && $this->event['tx_register4cal_fieldset'] != -2) {
                // get extension confArr
                $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['register4cal']);
                // page where records will be stored
                $sPid = '=' . intval($this->event['pid']) . ' ';
                if (isset($confArr['fieldStoragePid'])) {
                    $fieldStoragePid = intval($confArr['fieldStoragePid']);
                    if ($fieldStoragePid != 0)
                        $sPid = ' IN (' . intval($this->event['pid']) . ',' . $fieldStoragePid . ' )';
                }

                // if fieldset is set, read it, otherwise read first fieldset having the "isdefault" flag set
                if ($this->event['tx_register4cal_fieldset'] != 0 && $this->event['tx_register4cal_fieldset'] != -1) {
                    $where = 'uid=' . intval($this->event['tx_register4cal_fieldset']) . ' AND pid' . $sPid;
                } else {
                    $where = 'isdefault <> 0' . ' AND pid' . $sPid;
                }
                $where .= $TSFE->cObj->enableFields('tx_register4cal_fieldsets');

                //read fieldset
                $res = $TYPO3_DB->exec_SELECTquery('fields', 'tx_register4cal_fieldsets', $where);
                if (($row = $TYPO3_DB->sql_fetch_assoc($res))) {
                    //read fields
                    $fieldlist = $TYPO3_DB->cleanIntList($row['fields']);
                } else
                    $fieldlist = FALSE;
                $TYPO3_DB->sql_free_result($res);

                if (!$fieldlist === FALSE && !$fieldlist == '') {
                    // read fields and translate them
                    $select = '*';
                    $table = 'tx_register4cal_fields';
                    $where = 'uid IN (' . $fieldlist . ')' .
                            'AND sys_language_uid IN (0,-1)' .
                            $TSFE->cObj->enableFields($table);
                    $rows = Array();
                    $res = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
                    while (($row = $TYPO3_DB->sql_fetch_assoc($res))) {
                        $rows[$row['uid']] = $TSFE->sys_page->getRecordOverlay('tx_register4cal_fields', $row, $TSFE->sys_language_uid, $TSFE->config['config']['sys_language_overlay']);
                    }
                    $TYPO3_DB->sql_free_result($res);

                    // put fields in the same order as in fieldlist
                    $fieldarray = explode(',', $fieldlist);
                    foreach ($fieldarray as $field) {
                        if (isset($rows[$field])) {
                            $row = Array(
                                'name' => $rows[$field]['name'],
                                'caption' => $rows[$field]['caption'],
                                'type' => $rows[$field]['type'],
                                'options' => $rows[$field]['options'],
                                'width' => $rows[$field]['width'],
                                'height' => $rows[$field]['height'],
                                'isnumparticipants' => $rows[$field]['isnumparticipants'],
                                'defaultvalue' => $rows[$field]['defaultvalue'],
                            );

                            $userfields[$row['name']] = Array(
                                'conf' => $row,
                                'value' => FALSE,
                            );
                        }
                    }
                }
            }
        }
        return $userfields;
    }

    /**
     * Write registration as new registration record
     * @global t3lib_DB $TYPO3_DB
     */
    private function writeNewRegistrationRecord() {
        global $TYPO3_DB;

        $table = 'tx_register4cal_registrations';
        $TYPO3_DB->exec_INSERTquery($table, $this->registration);
        $this->registration['uid'] = $TYPO3_DB->sql_insert_id();
    }

    /**
     * Write registration as updated registration record
     * @global t3lib_DB $TYPO3_DB
     */
    private function writeUpdatedRegistrationRecord() {
        global $TYPO3_DB;

        $this->registration['tstamp'] = time();
        $table = 'tx_register4cal_registrations';
        $where = 'uid = ' . intval($this->registration['uid']);
        $TYPO3_DB->exec_UPDATEquery($table, $where, $this->registration);
    }

    /**
     * Delete registration record
     * @global t3lib_DB $TYPO3_DB
     */
    private function deleteRegistrationRecord() {
        // global $TYPO3_DB;

        $this->registration['deleted'] = 1;
        $this->writeUpdatedRegistrationRecord();
        /* This would really delete the record, which is not recommended in Typo3
          $table = 'tx_register4cal_registrations';
          $where = 'uid = ' . intval($this->registration['uid']);
          $TYPO3_DB->exec_DELETEquery($table, $where);
         */
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/model/class.tx_register4cal_registration_model.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/model/class.tx_register4cal_registration_model.php']);
}
?>