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
 * ThEr160909	0.4.0	Most coding went to class tx_register4cal_main
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_register4cal_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_register4cal_pi1';			// Same as class name
	var $scriptRelPath = 'pi1/class.tx_register4cal_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'register4cal';				// The extension key.
	var $pi_checkCHash = true;

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
		$this->pi_initPIflexForm();
	
		$pidlist = $this->pi_getPidList($this->pi_getFFvalue($this->cObj->data['pi_flexform'],'pages'),$this->pi_getFFvalue($this->cObj->data['pi_flexform'],'recursive'));  	
		$displayMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],'displayMode');
		
		if (!$pidlist) {
			$content = tx_register4cal_user1::errormessage('No storage folder set','There is no storage folder set for the plugin. Please notify the site administrator!');
		} elseif (!$displayMode) {
			$content = tx_register4cal_user1::errormessage('No display mode set','There is no display mode set for the plugin. Please notify the site administrator!');
		} else {
			$main = tx_register4cal_user1::getReg4CalMainClass();
			switch ($displayMode) {
				case 2:
					$content = $main->EventList($pidlist);
					break;
				case 3:
					$content = $main->ParticipantList($pidlist);
					break;
				default:
					$content = tx_register4cal_user1::errormessage('Display mode invalid','An invalid display mode is set for the plugin. Please notify the site administrator!');
					break;
			}
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php']);
}
        
?>