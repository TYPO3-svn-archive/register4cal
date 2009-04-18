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
 * ThEr230209	0.2.4	Initial development of class
 */ 

/**
 * Classes to extend the cal model. One class for each field
 * Each class defines the way, the field is being displayed in the
 * frontend editing forms
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */

require_once (t3lib_extMgm :: extPath('cal').'view/class.tx_cal_base_view.php');require_once(t3lib_extMgm::extPath('register4cal').'user/class.tx_register4cal_user1.php'); 

class tx_register4cal_fields{}	//This is to suppress the code warning in the extension manager

class tx_register4cal_activate extends tx_cal_base_view {	
	function start(&$moduleCaller){	
		//determine display value depending on display mode
		if (($moduleCaller->conf['view']=='edit_event') || $moduleCaller->conf['view']=='create_event') {
			$valOn  = $moduleCaller->object->row['tx_register4cal_activate']==1 ? ' selected="selected" ' : ' ';
			$valOff = $moduleCaller->object->row['tx_register4cal_activate']!=1 ? ' selected="selected" ' : ' ';
			$optOn  = $moduleCaller->cObj->stdWrap($valOn, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_on_stdWrap.']);
			$optOff  = $moduleCaller->cObj->stdWrap($valOff, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_off_stdWrap.']);
			
			$content = $moduleCaller->cObj->stdWrap($optOn.$optOff, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_stdWrap.']);
		} elseif ($moduleCaller->conf['view']=='confirm_event') {
			$optOn  = $moduleCaller->cObj->stdWrap($moduleCaller->object->row['tx_register4cal_activate'], $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_on_stdWrap.']);
			$optOff = $moduleCaller->cObj->stdWrap($moduleCaller->object->row['tx_register4cal_activate'], $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_off_stdWrap.']);
			$opt = $moduleCaller->object->row['tx_register4cal_activate']==1 ? $optOn : $optOff;
			$content = $moduleCaller->cObj->stdWrap($opt, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_activate_stdWrap.']);
		}
		return $content;
	}
}

class tx_register4cal_regstart extends tx_cal_base_view {	
	function start(&$moduleCaller){
		$dateformat = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.']['dateformat'];	
	
		$regstart = $moduleCaller->object->row['tx_register4cal_regstart'];
		if (is_numeric($regstart)) {
			//regstart is numeric --> timestamp: Convert to date
			$regstart = $regstart==0 ? '' : tx_register4cal_user1::formatDate(date('Ymd',$regstart),0,$dateformat);
		} else {
			//regstart is not numeric --> date: Convert to timestamp and back to ensure the timestamp-value is being displayed
			$regstart = tx_register4cal_user1::getTimestamp($regstart);
			$regstart = $regstart==0 ? '' : tx_register4cal_user1::formatDate(date('Ymd',$regstart),0,$dateformat);
		}
		
		//determine display value
		$content = $moduleCaller->cObj->stdWrap($regstart, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_regstart_stdWrap.']);
		$content = str_replace('###TX_REGISTER4CAL_REGSTART_VALUE###',$regstart,$content);
		return $content;
	}	
}

class tx_register4cal_regend extends tx_cal_base_view {	
	function start(&$moduleCaller){
		$dateformat = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.']['dateformat'];	
	
		$regend = $moduleCaller->object->row['tx_register4cal_regend'];
		if (is_numeric($regend)) {
			//regend is numeric --> timestamp: Convert to date
			$regend = $regend==0 ? '' : tx_register4cal_user1::formatDate(date('Ymd',$regend),0,$dateformat);
		} else {
			//regend is not numeric --> date: Convert to timestamp and back to ensure the timestamp-value is being displayed
			$regend = tx_register4cal_user1::getTimestamp($regend);
			$regend = $regend==0 ? '' : tx_register4cal_user1::formatDate(date('Ymd',$regend),0,$dateformat);
		}
		
		//determine display value
		$content = $moduleCaller->cObj->stdWrap($regend, $moduleCaller->conf['view.'][$moduleCaller->conf['view'].'.']['tx_register4cal_regend_stdWrap.']);
		$content = str_replace('###TX_REGISTER4CAL_REGEND_VALUE###',$regend,$content);
		return $content;
	}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/model/class.tx_register4cal_fields.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/model/class.tx_register4cal_fields.php']);
}
?>

