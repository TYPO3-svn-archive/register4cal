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
 * class.tx_register4cal_listreg.php
 *
 * Provide service class to process our own markers in cal templates 
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('cal') . 'view/class.tx_cal_base_view.php');

/**
 * Class to handle marker ###MODULE__tx_register4cal_listreg###
 * --> Registration form in list view: Fields for single event
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_listreg extends tx_cal_base_view {
	/**
	 * Render marker
	 * @param tx_cal_base_view $parent parent cal view class
	 * @return string HTML content for marker
	 */
	function start(&$parent) {
                global $TX_REGISTER4CAL_DATA;

		if ($GLOBALS['TSFE']->fe_user->user['uid'] != 0 && $parent->cachedValueArray['tx_register4cal_activate']) {   // A frontend user is logged in
			if (isset($TX_REGISTER4CAL_DATA['controller'])) {
				$content = $TX_REGISTER4CAL_DATA['controller']->ListViewRegistration_Event($parent->row);
			}
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/calview/class.tx_register4cal_listreg.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/calview/class.tx_register4cal_listreg.php']);
}
?>