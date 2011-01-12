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
 * class.tx_register4cal_activate.php
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
 * Class to handle marker ###MODULE__tx_register4cal_avtivate###
 * --> Field "Registration active" for event frontend editing
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_activate extends tx_cal_base_view {
	/**
	 * Render marker
	 * @param tx_cal_base_view $parent parent cal view class
	 * @return string HTML content for marker
	 */
	function start(&$parent) {
		//determine display value depending on display mode
		if (($parent->conf['view'] == 'edit_event') || $parent->conf['view'] == 'create_event') {
			$valOn = $parent->object->row['tx_register4cal_activate'] == 1 ? ' selected="selected" ' : ' ';
			$valOff = $parent->object->row['tx_register4cal_activate'] != 1 ? ' selected="selected" ' : ' ';
			$optOn = $parent->cObj->stdWrap($valOn, $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_activate_on_stdWrap.']);
			$optOff = $parent->cObj->stdWrap($valOff, $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_activate_off_stdWrap.']);
			$content = $parent->cObj->stdWrap($optOn . $optOff, $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_activate_stdWrap.']);
		} elseif ($parent->conf['view'] == 'confirm_event') {
			$optOn = $parent->cObj->stdWrap($parent->object->row['tx_register4cal_activate'], $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_activate_on_stdWrap.']);
			$optOff = $parent->cObj->stdWrap($parent->object->row['tx_register4cal_activate'], $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_activate_off_stdWrap.']);
			$opt = $parent->object->row['tx_register4cal_activate'] == 1 ? $optOn : $optOff;
			$content = $parent->cObj->stdWrap($opt, $parent->conf['view.'][$parent->conf['view'] . '.']['tx_register4cal_activate_stdWrap.']);
		}
		return $content;
	}

	/**
	 * Validate changes for tx_register4cal_activate
	 * @param integer $eventId Uid of the changed event
	 * @param array $rule  Rule with constrains for field
	 * @return boolean TRUE: Field ok, FALSE: Field not ok
	 */
	public function validate($eventId, $rule) {
		require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_validation_controller.php');
		$newEvent = t3lib_div::GParrayMerged('tx_cal_controller');
		return tx_register4cal_validation_controller::checkActivate($eventId, $newEvent, $error);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/calview/class.tx_register4cal_activate.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/calview/class.tx_register4cal_activate.php']);
}
?>