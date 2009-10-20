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
	function start(&$moduleCaller){	
		//determine display value depending on display mode
		if (($moduleCaller->conf['view'] == 'edit_event') || $moduleCaller->conf['view'] == 'create_event') {
			$valOn  = $moduleCaller->object->row['tx_register4cal_activate'] == 1 ? ' selected="selected" ' : ' ';
			$valOff = $moduleCaller->object->row['tx_register4cal_activate'] != 1 ? ' selected="selected" ' : ' ';
			$optOn  = $moduleCaller->cObj->stdWrap($valOn, $moduleCaller->conf['view.'][$moduleCaller->conf['view'] . '.']['tx_register4cal_activate_on_stdWrap.']);
			$optOff  = $moduleCaller->cObj->stdWrap($valOff, $moduleCaller->conf['view.'][$moduleCaller->conf['view'] . '.']['tx_register4cal_activate_off_stdWrap.']);
			$content = $moduleCaller->cObj->stdWrap($optOn . $optOff, $moduleCaller->conf['view.'][$moduleCaller->conf['view'] . '.']['tx_register4cal_activate_stdWrap.']);
		} elseif ($moduleCaller->conf['view']=='confirm_event') {
			$optOn  = $moduleCaller->cObj->stdWrap($moduleCaller->object->row['tx_register4cal_activate'], $moduleCaller->conf['view.'][$moduleCaller->conf['view'] . '.']['tx_register4cal_activate_on_stdWrap.']);
			$optOff = $moduleCaller->cObj->stdWrap($moduleCaller->object->row['tx_register4cal_activate'], $moduleCaller->conf['view.'][$moduleCaller->conf['view'] . '.']['tx_register4cal_activate_off_stdWrap.']);
			$opt = $moduleCaller->object->row['tx_register4cal_activate'] == 1 ? $optOn : $optOff;
			$content = $moduleCaller->cObj->stdWrap($opt, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_stdWrap.']);
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_activate.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_activate.php']);
}
?>