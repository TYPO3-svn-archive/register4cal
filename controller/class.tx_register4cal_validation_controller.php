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
 * class.tx_register4cal_validation_controller.php
 *
 * Class implementing a controller for validation during editing cal-records
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

/**
 * Class implementing a controller for validation during editing cal-records
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_validation_controller {
	// TODO SEV9 Version 0.7.1: Localize error messages
	
	/* =========================================================================
	 * Public static methods to perform sets of checks
	 * ========================================================================= */
	/**
	 * Perform all checks for new event record. Called via hook, if event has been
	 * changed in the backend
	 *
	 * @param	integer		$eventUid: Uid of the event to be changed
	 * @param	array		$newEvent: New event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function onBackendEventUpdate($eventId, $newEvent, &$error) {
		$error = '';
		// get old event
		if (!self::getEvent($eventId, $oldEvent, &$error)) return false;

		// check if registration has been deactivated
		if (!self::checkActivate($oldEvent, $newEvent, &$error)) return false;

		// check if maximum number of attendees has been reduced
		if (!self::checkMaxattendees($oldEvent, $newEvent, &$error)) return false;

		// check if waitlist has been deactivated
		if (!self::checkWaitlist($olfEvent, $newEvent, &$error)) return false;

		// All checks performed, no errors found
		return true;
	}

	/* =========================================================================
	 * Public static methods to perform single checks
	 * ========================================================================= */
	/**
	 * Check if registration has been deactivated, which is not allowed if registrations exist
	 * @param integer/array $oldEvent Old event data or id of old event
	 * @param array $newEvent  New event data
	 * @param string $error Return value for error message
	 * @return boolean TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkActivate($oldEvent, $newEvent, &$error) {
		if (isset($newEvent['tx_register4cal_activate'])) {
			if (!is_array($oldEvent)) self::getEvent ($oldEvent, &$oldEvent, $error);
			if ($newEvent['tx_register4cal_activate'] == 0 && $oldEvent['tx_register4cal_activate'] != 0) {
				$numberOfRegistrations = self::countRegistratons($oldEvent);
				if ($numberOfRegistrations > 0) $error = 'You can not deactivate registration as there are already ' . $maxReg . ' registrations!';
			}
		}
		return!$error;
	}

	/**
	 * Check if maximum number of attendees has been reduced to a number less than the current number of registrations
	 * @param integer/array $oldEvent Old event data or id of old event
	 * @param array $newEvent  New event data
	 * @param string $error Return value for error message
	 * @return boolean TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkMaxattendees($oldEvent, $newEvent, &$error) {
		if (isset($newEvent['tx_register4cal_maxattendees'])) {
			if (!is_array($oldEvent)) self::getEvent ($oldEvent, &$oldEvent, $error);
			if ($newEvent['tx_register4cal_maxattendees'] < $oldEvent['tx_register4cal_maxattendees']) {
				$numberOfRegistrations = self::countRegistratons($oldEvent, 1);
				if ($numberOfRegistrations > $newEvent['tx_register4cal_maxattendees']) $error = 'You can not reduce max. number of attendees to ' . $newEvent['tx_register4cal_maxattendees'] . ' as there are already ' . $numberOfRegistrations . ' registrations!';
			}
		}
		return!$error;
	}

	/**
	 * Check if waitlist has been deactivated, which is not allowed if entries in waitlist exist
	 * @param integer/array $oldEvent Old event data or id of old event
	 * @param array $newEvent  New event data
	 * @param string $error Return value for error message
	 * @return boolean TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkWaitlist($oldEvent, $newEvent, &$error) {
		if (isset($newEvent['tx_register4cal_waitlist'])) {
			if (!is_array($oldEvent)) self::getEvent ($oldEvent, &$oldEvent, $error);
			if ($newEvent['tx_register4cal_waitlist'] == 0 && $oldEvent['tx_register4cal_waitlist'] != 0) {
				$numberOfWaitlistEntries = self::countRegistratons($oldEvent, 2);
				if ($numberOfWaitlistEntries > 0) $error = 'You can not deactivate waitlist as there are already ' . $numberOfWaitlistEntries . ' waitlist entries!';
			}
		}
		return!$error;
	}

	/* =========================================================================
	 * private static methods to read data
	 * ========================================================================= */
	private static function countRegistratons($event, $status=0) {
		global $TYPO3_DB, $TSFE;

		// Count registrations
		$select = 'sum(numattendees) as number';
		$table = 'tx_register4cal_registrations';
		$where = 'cal_event_uid=' . intval($event['uid']) .
				' AND pid=' . intval($event['pid']) .
				' AND deleted=0';
		$where .= ($status) ? ' AND status=' . intval($status) : ' AND status <> 3';
		$rows = $TYPO3_DB->exec_SELECTgetRows($select, $table, $where);
		if (count($rows) != 0) $count = $rows[0]['number'];
		else $count=0;
		return $count;
	}

	/**
	 * Read the old event data from the databaase
	 *
	 * @param	integer		$eventUid: Uid of the event to be read
	 * @param	array		$oldEvent: Return value for old event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	private static function getEvent($eventUid, &$oldEvent, &$error) {
		$eventUid = intval($eventUid);
		$table = 'tx_cal_event';
		$fields = '*';
		$where = 'uid=' . $eventUid;
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);
		if (count($rows) != 1) {
			$oldEvent = Array();
			$error = 'Record ' . $eventUid . ' not found!';
		} else {
			$oldEvent = $rows[0];
			$error = '';
		}
		return!$error;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_validation_controller.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_validation_controller.php']);
}
?>
