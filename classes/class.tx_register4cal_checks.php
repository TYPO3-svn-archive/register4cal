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
 * class.tx_register4cal_checks.php
 *
 * Check changed event data.
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(t3lib_extMgm::extPath('register4cal') . 'classes/class.tx_register4cal_user1.php'); 

/**
 * Check if changed event data is valid
 *
 * @author 	Thomas Ernst <typo3@thernst.de>
 * @package 	TYPO3
 * @subpackage 	tx_register4cal
 */
 
class tx_register4cal_checks {

	/**
	 * Perform all checks for new event record. Called via hook, if event has been
	 * changed in the backend
	 *
	 * @param	integer		$eventUid: Uid of the event to be changed
	 * @param	array		$newEvent: New event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkAll_Backend($eventUid, $newEvent, &$error) {
		$error = '';
			// get old event
		if (!self::getEvent($eventUid, $oldEvent, $error)) return false;

			// check if registration has been deactivated
		if (!self::checkActivate($oldEvent, $newEvent, &$error)) return false;

			// check if maximum number of attendees has been reduced
		if (!self::checkMaxattendees($oldEvent, $newEvent, &$error)) return false;
						
			// check if waitlist has been deactivated
		if (!self::checkWaitlist($olfEvent, $newEvent, &$error)) return false;
		
			// All checks performed, no errors found
		return true;
	}

	/**
	 * Check if registration has been deactivated, which is not allowed if registrations exist
	 *
	 * @param	array		$oldEvent: Old event data
	 * @param	array		$newEvent: New event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkActivate($oldEvent, $newEvent, &$error) {
		if (isset($newEvent['tx_register4cal_activate'])) {
			if ($newEvent['tx_register4cal_activate'] == 0 && $oldEvent['tx_register4cal_activate'] != 0) {
				$maxReg = self::getMaxRegistrations($oldEvent, 0);
				if ($maxReg > 0) {
					$error = 'Cant deactivate registration as there are already ' . $maxReg . ' registrations!';
				}
			}
		}	
		return !($error);
	}

	/**
	 * Check if maximum number of attendees has been reduced to a number less than the current number of registrations
	 *
	 * @param	array		$oldEvent: Old event data
	 * @param	array		$newEvent: New event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkMaxattendees($oldEvent, $newEvent, &$error) {
		if (isset($newEvent['tx_register4cal_maxattendees'])) {
			if ($newEvent['tx_register4cal_maxattendees'] < $oldEvent['tx_register4cal_maxattendees']) {
				$maxReg = self::getMaxRegistrations($oldEvent, 1);
				if ($maxReg > $newEvent['tx_register4cal_maxattendees']) {
					$error = 'Cant reduce max. number of attendees to '. $newEvent['tx_register4cal_maxattendees'].' as there are already ' . $maxReg . ' registrations!';
				}
			}
		}
		return !($error);
	}

	/**
	 * Check if waitlist has been deactivated, which is not allowed if entries in waitlist exist
	 *
	 * @param	array		$oldEvent: Old event data
	 * @param	array		$newEvent: New event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function checkWaitlist($oldEvent, $newEvent, &$error) {
		if (isset($newEvent['tx_register4cal_waitlist'])) {
			if ($newEvent['tx_register4cal_waitlist'] == 0 && $oldEvent['tx_register4cal_waitlist'] != 0) {
				$maxReg = self::getMaxRegistrations($oldEvent, 2);
				if ($maxReg > 0) {
					$error = 'Cant deactivate waitlist as there are already ' . $maxReg . ' waitlist entries!';
				}
			}
		}
		return !($error);
	}

	/**
	 * Read the old event data from the databaase
	 *
	 * @param	integer		$eventUid: Uid of the event to be read
	 * @param	array		$oldEvent: Return value for old event data
	 * @param	string		$error: Return value for error message
	 * @return	boolean		TRUE: everything ok, FALSE: errors occured, see $error for detailed error message
	 */
	public static function getEvent($eventUid, &$oldEvent, &$error) {
		$eventUid = intval($eventUid);
		$table = 'tx_cal_event';
		$fields = '*';
		$where = 'uid='.$eventUid;
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);
		if (count($rows) != 1) {
			$oldEvent = Array();
			$error = 'Record ' . $eventUid . ' not found!';
			return false;
		} else {
			$oldEvent = $rows[0];	
			$error = '';
			return true;
		}
	}

	/**
	 * Determine the maximum number of registrations in a certain registration status
	 *
	 * @param	array		$Event: Event data of the event for which the registrations should be determiend
	 * @param	integer		$regStatus: Status of registrations to be counted (0: active and waitlist, 1: active, 2: waitlist, 3: cancelled)
	 * @return	integer		number of registrations with given status
	 */
	private static function getMaxRegistrations($event, $regStatus=0) {
		$max = 0;
		$eventGetDates = self::getEventGetDates($event['uid']);
		foreach ($eventGetDates as $getDate) {
				// count registrations for each getdate-value
			$statusCount = tx_register4cal_user1::getRegistrationCount($event['uid'], $getDate, $event['pid']);
			switch ($regStatus) {
				case 0:
					$val = $statusCount[1] + $statusCount[2];
					break;
				default:
					$val = $statusCount[$regStatus];
			}
			
			if ($val > $max) $max = $val;			
		}	
		return $max;
	}
	
	/**
	 * Read all event dates from registration table for which registrations are stored 
	 * (required for recurring events with registrations)
	 *
	 * @param	integer		$eventUid: Uid of the event for which the dates should be read
	 * @return	array		Array containing all found dates
	 */	
	private static function getEventGetDates($eventUid) {
		$table = 'tx_register4cal_registrations';
		$fields = 'DISTINCT cal_event_getdate';
		$where = 'cal_event_uid='.intval($eventUid).' AND status=1 '.t3lib_BEfunc::BEenableFields($table).t3lib_BEfunc::deleteClause($table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$data = Array();
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) != 0) {
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$data[] = $row['cal_event_getdate'];
			}
		}
		return $data;
	}
}
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_checks.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_checkshp']);
}
 
?>
