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
 * Process hooks from cal extension in event single view and event list view
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
 * Display the registration form in single event view by processing the hook 'preFinishViewRendering'
 *
 * @author 	Thomas Ernst <typo3@thernst.de>
 * @package 	TYPO3
 * @subpackage 	tx_register4cal
 */
class tx_register4cal_fehooks { 
	/***********************************************************************************************************************************************************************
	*
	* Hook from extension cal to add the registration form
	*
	**********************************************************************************************************************************************************************/
	function preFinishViewRendering() {}
	function postSearchForObjectMarker($otherThis, &$content) {
		//get piVars from tx_cal_controler and user data
		$data = t3lib_div::GParrayMerged('tx_cal_controller');
		$user = $GLOBALS['TSFE']->fe_user->user;
		
		//Conditions to display the registration form (first step)
		if ($otherThis->conf['view'] == 'event' && $data['uid'] != 0) {	/* Single event view displaying an event */
			if ($user['uid'] != 0) {			/* An frontend user is logged in     */					
				$main = tx_register4cal_user1::getReg4CalMainClass();
				$content .= $main->SingleEventRegistration($data);
			} else {					/* no frontend user is logged in */
				//Check if a onetimepid is given. If this is the case, offer link for
				//creating a onetimeaccount using onetimeaccount
				$disableNeedLoginForm = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.']['disableNeedLoginForm'];
				if ($disableNeedLoginForm == 0) {
					$main = tx_register4cal_user1::getReg4CalMainClass();
					$content .= $main->SingleEventLogin();
				}
			}
		}
	}
	
	/***********************************************************************************************************************************************************************
	*
	* Use hook of cal extension to store registrations entered in the list view
	*
	**********************************************************************************************************************************************************************/
	function drawlistClass() {}
	function preListRendering($events, &$class) {
		//get piVars from tx_register4cal_main
		$data = t3lib_div::GParrayMerged('tx_register4cal_main');
		//if we have an tx_register4cal_main-Array, check if we have registrations to store
		if (is_array($data)) {
			$main = tx_register4cal_user1::getReg4CalMainClass();
			$main->ListViewRegistrationStore($data);
		}
	}	
	
	/***********************************************************************************************************************************************************************
	*
	* Use hook of cal extension to add form to event list view if required
	*
	**********************************************************************************************************************************************************************/
	function postListRendering(&$content, $events, &$class) {
		//if there are some tx_register4cal_main parts in the content, surround the whole content with a form for the registration
		if (strpos($content, 'tx_register4cal_main') != 0) {
			$main = tx_register4cal_user1::getReg4CalMainClass();
			$content = $main->ListViewRegistrationForm($content);
		}
	}
}

 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_fehooks.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_fehooks.php']);
}
	 
?>
