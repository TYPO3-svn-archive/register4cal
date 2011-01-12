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
 * class.tx_register4cal_regstart.php
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
require_once (t3lib_extMgm::extPath('cal') . 'view/class.tx_cal_base_view.php');

/**
 * Class to handle marker ###MODULE__tx_register4cal_regstart###
 * --> Field "Start of registration period" for event frontend editing
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_regstart extends tx_cal_base_view {
	/**
	 * Render marker
	 * @param tx_cal_base_view $parent parent cal view class
	 * @return string HTML content for marker
	 */
	function start(&$parent) {
		global $TSFE;
		//Get some configuration
		$dateformat = $TSFE->tmpl->setup['plugin.']['tx_register4cal_pi1.']['dateformat'];
		$useDateSelector = $TSFE->tmpl->setup['plugin.']['tx_register4cal_pi1.']['edit.']['useDateSelector'];
		$dateSelectorConf = $TSFE->tmpl->setup['plugin.']['tx_register4cal_pi1.']['edit.'];

		//get value for field
		$regstart = $parent->object->row['tx_register4cal_regstart'];
		if (empty($regstart)) {
			//regstart is empty --> keep it empty
			$regstart = '';
		} elseif (is_numeric($regstart)) {
			//regstart is numeric --> timestamp: Convert to date
			$regstart = $regstart == 0 ? '' : tx_register4cal_datetime::formatDate(date('Ymd', $regstart), 0, $dateformat);
		} else {
			//regstart is not numeric --> date: Convert to timestamp and back to ensure the timestamp-value is being displayed
			$regstart = tx_register4cal_datetime::getTimestamp($regstart);
			$regstart = $regstart == 0 ? '' : tx_register4cal_datetime::formatDate(date('Ymd', $regstart), 0, $dateformat);
		}
		//get display value
		$content = $parent->cObj->stdWrap($regstart, $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_regstart_stdWrap.']);
		$content = str_replace('###TX_REGISTER4CAL_REGSTART_VALUE###', $regstart, $content);

		//include dateselector if activated and extension rlmp_dateselectlib is available
		$selector = '';
		if ($useDateSelector == 1) {
			if (t3lib_extMgm::isLoaded('rlmp_dateselectlib')) {
				tx_rlmpdateselectlib::includeLib();
				$selector = tx_rlmpdateselectlib::getInputButton('tx_register4cal_regstart', $dateSelectorConf);
			}
		}
		$content = str_replace('###REGSTART_SELECTOR###', $selector, $content);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/calview/class.tx_register4cal_regstart.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/calview/class.tx_register4cal_regstart.php']);
}
?>