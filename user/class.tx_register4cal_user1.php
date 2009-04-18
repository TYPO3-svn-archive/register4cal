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
 * ThEr010309   0.2.0   Added several general functions from tx_register4cal_pi1 for general use
 * ThEr190309	0.2.2	FormatDateTime: Timezone conversion must not be performed for allday-events
 * ThEr100409	0.2.4	Added helper functions for frontend editing
 */ 

/**
 * Userclass for the 'register4cal' extension.
 * Contains the functions for displaying additional data in the backend and also
 * some general functions to be used in several classes of this extension.
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
 
require_once(t3lib_extMgm::extPath('cal').'res/pearLoader.php'); 
 
class tx_register4cal_user1 {

/***********************************************************************************************************************************************************************
 *
 * Helper functions for frontend editing
 *
 **********************************************************************************************************************************************************************/
	//ThEr100409: Start of changes -----------------------------------------------------------------------------------------------------
	/*
         * UserFunction to convert a date into a timestamp
         *
	 * @param	string		$content: Content the element
	 * @param	array		$conf: Configuration of stdWrap
	 *
         * @return 	new value for the content element
         */	
	function convertToTimestamp($content, $conf) {
		$timestamp = $this->getTimestamp($content);
		return($timestamp);
	}

	/*
         * Change a date in any format to an unix timestamp
	 * Coding found at http://www.typo3.net/index.php?id=13&action=list_post&tid=87564, Thanks to Alex
         *
	 * @param	string		$string: Date to change
	 * @param	string		$default: Default value used in case of errors
	 * @param	int		$timestamp: 1: Return timestamp, 0: return "Y-m-d"
	 *
         * @return 	converted date
         */	
	static function getTimestamp($string, $default = 'now', $timestamp = 1) {
		$error = 0; // no error at the beginning
		$string = str_replace(array('-', '_', ':', '+', ',', ' '), '.', $string); // change 23-12-2009 -> 23.12.2009 AND "05:00 23.01.2009" -> 05.00.23.01.2009
		if (method_exists('t3lib_div', 'trimExplode')) $dateParts = t3lib_div::trimExplode('.', $string, 1); else $dateParts = explode('.', $string); // split at .
       
		if (count($dateParts) === 3) { // only if there are three parts like "23.12.2009"
			if (strlen($dateParts[0]) <= 2 && strlen($dateParts[1]) <= 2 && strlen($dateParts[2]) <= 2) { // xx.xx.xx
				$string = strtotime($dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0]); // change to timestamp
			}
			elseif (strlen($dateParts[0]) == 4 && strlen($dateParts[1]) <= 2 && strlen($dateParts[2]) <= 2) { // xxxx.xx.xx
				$string = strtotime($dateParts[0].'-'.$dateParts[1].'-'.$dateParts[2]); // change to timestamp
			}
			elseif (strlen($dateParts[0]) <= 2 && strlen($dateParts[1]) <= 2 && strlen($dateParts[2]) == 4) { // xx.xx.xxxx
				$string = strtotime($dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0]); // change to timestamp
			}
			else { // error
				$error = 1; // error
			}
		} elseif (count($dateParts) === 5) { // only if there are five parts like "05.00.23.01.2009"
			$string = strtotime($dateParts[4].'-'.$dateParts[3].'-'.$dateParts[2].' '.$dateParts[0].':'.$dateParts[1].':00'); // change to timestamp
		} else { // more than 3 parts - so error
			$error = 1; // error
		}
		$string = date('Y-m-d', $string); // For default: change 1234567 -> 1.1.1979
		if ($timestamp) $string = strtotime($string); // Change back 1.1.1979 -> 1234567
		if ($error) $string = ($default == 'now' ? time() : $default); // show default value
       
		return $string;
	}
	//ThEr100409: End of changes -------------------------------------------------------------------------------------------------------

/***********************************************************************************************************************************************************************
 *
 * Backend display
 *
 **********************************************************************************************************************************************************************/	
	
	/* render the additional data for display in the backend */
	function additionalDataForBackend($PA, $fobj) {
		$additional_data = unserialize($PA['itemFormElValue']);
		$additional_fields = '';
		if (is_array($additional_data)) {
                	reset($additional_data);
                	while (list($name, $field) = each($additional_data)) {
				$caption = $field['caption'][$this->data['language']] != '' ? $field['caption'][$this->data['language']] : $field['caption']['default'];
				$additional_fields .= '<tr><td width= 100px;><b>'. htmlspecialchars($caption).'</b></td><td>'.htmlspecialchars($field['value']).'</td></tr>';
                	}
			$additional_fields = '<table width=100% border = 1 style="border:1px solid black;border-collapse:collapse;">'.$additional_fields.'</table>';
		}
		return $additional_fields;
	}
	
/***********************************************************************************************************************************************************************
 *
 * General functions to be used in several classes of this extension
 *
 **********************************************************************************************************************************************************************/	
	
	/*
         * Prepare a formated startdate/-time and enddate/-time
	 *
	 * Hint:
	 *  If event- and registration information have been retrieved in one select, they are contained in a single associative array.
	 *  In this case, this array can be assigned to both $eventRow and $registrationRow
         *
	 * @param	array		$eventRow: associative array containing the event record
	 * @param	array		$registrationRow: associative array containing the registration record for the event
	 * @param	string		$formatedStart: Returns the formated start date/time
	 * @param	string		$formatedEnd: Returns the formated end date/time
	 *
         * @return 	nothing
         */	
	function FormatDateTime($eventRow, $RegistrationRow, &$formatedStart, &$formatedEnd, $dateformat, $timeformat, $allday_String) {
		//format date and time
		if ($eventRow['freq'] == 'none' || empty($RegistrationRow)) {
			//single event
			$date_start = $eventRow['start_date'];
			$date_end = $eventRow['end_date'];
		} else {
			//recurring event, take date from registration record
			$date_start = $RegistrationRow['cal_event_getdate'];
			//ThEr010309: Start of changes -------------------------------------------------------------------------	
			//Calculate end date of single event based on start date of single event and length of recuring event
			//$date_end = $RegistrationRow['cal_event_getdate'];
			$length = (strtotime($eventRow['end_date']) - strtotime($eventRow['start_date']));
			$date_end = date(Ymd,strtotime($RegistrationRow['cal_event_getdate']) + $length);
			//ThEr010309: End of changes ---------------------------------------------------------------------------	
		}
		
		//t3lib_div::debug($date_start);
		//t3lib_div::debug($date_end);
		
		if ($eventRow['allday'] == 0) {
			//timed event
			$time_start = $eventRow['start_time'];
			$time_end =  $eventRow['end_time'];
			$format = $dateformat.' '.$timeformat;
			$timezone = $eventRow['timezone'];					//ThEr190309
		} else {
			$time_start = 0;
			$time_end = 0;
			$format = $dateformat;
			$timezone = '';								//ThEr190309

		}
		//$timezone = $eventRow['timezone'];						//ThEr190309
		$formatedStart = $this->formatDate($date_start,$time_start,$format, $timezone);
		$formatedEnd = $this->formatDate($date_end,$time_end,$format, $timezone);	
		if ($eventRow['allday'] != 0) {
			$formatedStart .=' '.$allday_String;
			$formatedEnd ='';
		}	
	}

	/*
         * Brings date and time in a readable format and performs timezone conversion if necessary
	 *      
         * @param  	integer		$date: Date to format (Should be format Ymd, such as 20090221)
	 * @param	integer		$time: Time to format (Should be in seconds since midnight)
	 * @param	string		$format: Format which should be provided (PEAR-Syntax)
	 * @param	string		$timezone: TZ to which the date/time should be convertet. Source-TZ is the system's default TZ.
	 *
         * @return 	string		Formated and converted date/time
         */
	//function formatDate($date, $time, $format, $timezone='') {			//ThEr100409
	static function formatDate($date, $time, $format, $timezone='') {		//ThEr100409
		$dateObj = new tx_cal_date(intval($date), 'Ymd');
		$dateObj->setHour(intval(date('H',$time)));
		$dateObj->setMinute(intval(date('i',$time)));
		$dateObj->setSecond(intval(date('s',$time)));
		if ($timezone!='') {
			//A timezone conversion somehow changes the default time zone.
			//We thus need to determine it before and reset it afterwards ...
			$default_timezone = date_default_timezone_get();
			$dateObj->convertTZbyID($timezone) ;
			date_default_timezone_set($default_timezone);
		}
		return $dateObj->format($format);
	}
	
	/*
         * Provide the name of the organizer for an event
	 *
	 * @param	array		$Row: associative array containing the event record
	 * @param	string		&$name: returns the name of the organizer
	 * @param	string		&$email: returns the email of the organizer
	 *
         * @return 	string		Name of the organizer of an event
         */	
	function getOrganizerData($event, &$name, &$email) {
		if ($event['organizer_id']==0) {
			//Organizer in event record, email not available
			$name = $event['organizer'];
			$email ='';
		} else {
			//Organizer and email in separate organizer record
			$select = 'name, email';
			$table = 'tx_cal_organizer';
			$where = 'uid='.intval($event['organizer_id']);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$name = $row['name'];
			$email = $row['email'];
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/user/class.tx_register4cal_user1.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/user/class.tx_register4cal_user1.php']);
}
        
?>
