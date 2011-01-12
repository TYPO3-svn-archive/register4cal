<?php

/* * *************************************************************
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
 * ************************************************************* */
/**
 * class.tx_register4cal_datetime.php
 *
 * Provide static functions for date and time handling
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('cal') . 'res/pearLoader.php');

/**
 * static functions for date and time handling
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_datetime {
	/**
	 * Convert a date into a timestamp
	 * @param string $content Content the element
	 * @param array $conf Configuration of stdWrap
	 * @return integer new value for the content element
	 */
	public static function convertToTimestamp($content, $conf) {
		if (!empty($content)) $timestamp = self::getTimestamp($content);
		return($timestamp);
	}

	/**
	 * Change a date in any format to an unix timestamp
	 * Coding found at http://www.typo3.net/index.php?id=13&action=list_post&tid=87564, Thanks to Alex
	 * @param string $string Date to change
	 * @param string $default Default value used in case of errors
	 * @param int $timestamp 1: Return timestamp, 0: return "Y-m-d"
	 * @return integer converted date
	 */
	public static function getTimestamp($string, $default = 'now', $timestamp = 1) {
		$error = 0; // no error at the beginning
		$string = str_replace(array('-', '_', ':', '+', ',', ' '), '.', $string); // change 23-12-2009 -> 23.12.2009 AND "05:00 23.01.2009" -> 05.00.23.01.2009
		if (method_exists('t3lib_div', 'trimExplode')) $dateParts = t3lib_div::trimExplode('.', $string, 1); else $dateParts = explode('.', $string); // split at .

			if (count($dateParts) === 3) { // only if there are three parts like "23.12.2009"
			if (strlen($dateParts[0]) <= 2 && strlen($dateParts[1]) <= 2 && strlen($dateParts[2]) <= 2) { // xx.xx.xx
				$string = strtotime($dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]); // change to timestamp
			} elseif (strlen($dateParts[0]) == 4 && strlen($dateParts[1]) <= 2 && strlen($dateParts[2]) <= 2) { // xxxx.xx.xx
				$string = strtotime($dateParts[0] . '-' . $dateParts[1] . '-' . $dateParts[2]); // change to timestamp
			} elseif (strlen($dateParts[0]) <= 2 && strlen($dateParts[1]) <= 2 && strlen($dateParts[2]) == 4) { // xx.xx.xxxx
				$string = strtotime($dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]); // change to timestamp
			} else { // error
				$error = 1; // error
			}
		} elseif (count($dateParts) === 5) { // only if there are five parts like "05.00.23.01.2009"
			$string = strtotime($dateParts[4] . '-' . $dateParts[3] . '-' . $dateParts[2] . ' ' . $dateParts[0] . ':' . $dateParts[1] . ':00'); // change to timestamp
		} else { // more than 3 parts - so error
			$error = 1; // error
		}
		$string = date('Y-m-d', $string);   // For default: change 1234567 -> 1.1.1979
		if ($timestamp) {
			$string = strtotime($string);
		}   // Change back 1.1.1979 -> 1234567
		if ($error) {
			$string = ($default == 'now' ? time() : $default);
		}// show default value

		return $string;
	}

	/**
	 * Brings date and time in a readable format and performs timezone conversion if necessary
	 * @param integer $date Date to format (Should be format Ymd, such as 20090221)
	 * @param integer $time Time to format (Should be in seconds since midnight)
	 * @param string $format Format which should be provided (PEAR-Syntax)
	 * @param string $timezone TZ to which the date/time should be converted. Source-TZ is the system's default TZ.
	 * @return 	string Formated and converted date/time
	 */
	static function formatDate($date, $time, $format, $timezone='') {
		$dateObj = new tx_cal_date(intval($date), 'Ymd');
		$dateObj->setHour(intval(date('H', $time)));
		$dateObj->setMinute(intval(date('i', $time)));
		$dateObj->setSecond(intval(date('s', $time)));
		if ($timezone != '') {
			//A timezone conversion somehow changes the default time zone.
			//We thus need to determine it before and reset it afterwards ...
			$default_timezone = date_default_timezone_get();
			$dateObj->convertTZbyID($timezone);
			date_default_timezone_set($default_timezone);
		}
		return $dateObj->format($format);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_datetime.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_datetime.php']);
}
?>