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
 * class.tx_register4cal_fieldset.php
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

require_once (t3lib_extMgm::extPath('cal').'view/class.tx_cal_base_view.php');

/**
 * Class to handle marker ###MODULE__tx_register4cal_fieldset###
 * --> Field "fieldset" for event frontend editing
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_fieldset extends tx_cal_base_view {	
	function start(&$moduleCaller){

			//get value for field		
		$fieldset = $moduleCaller->conf['view'] == 'create_event' ?  -1 : intval($moduleCaller->object->row['tx_register4cal_fieldset']);
		
			//determine display value depending on display mode
		if (($moduleCaller->conf['view'] == 'edit_event') || $moduleCaller->conf['view'] == 'create_event') {
			$fieldsets = Array();
			$fieldsets[-2] = $moduleCaller->cObj->stdWrap('',Array('dataWrap' => '{LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_fieldset.option.2}'));
			$fieldsets[-1] = $moduleCaller->cObj->stdWrap('',Array('dataWrap' => '{LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_fieldset.option.1}'));
		
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, name','tx_register4cal_fieldsets','pid=' . intval($moduleCaller->object->row['pid']) . $moduleCaller->cObj->enableFields('tx_register4cal_fieldsets'));
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$fieldsets[$row['uid']] = $row['name'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			
			$content = '';
			foreach($fieldsets as $uid => $name) {
				$sel = $uid == $fieldset ? ' selected' : '';
				$option = '<option' . $sel . ' value="' . $uid . '">' . $name . '</option>';
				$content .= $option;
			}				
		} elseif ($moduleCaller->conf['view']=='confirm_event') {
			switch ($fieldset) {
				case -2:
					$content = $moduleCaller->cObj->stdWrap('',Array('dataWrap' => '{LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_fieldset.option.2}'));
					break;
				case -1:
					$content = $moduleCaller->cObj->stdWrap('',Array('dataWrap' => '{LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_fieldset.option.1}'));
					break;
				default:
					$GLOBALS['TYPO3_DB']->debugOutput = true;
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('name','tx_register4cal_fieldsets','uid=' . $fieldset . $moduleCaller->cObj->enableFields('tx_register4cal_fieldsets'));
					if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) 
						$content = $row['name'];
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					break;
			}
		}		
		
		//get display value
		$content = $moduleCaller->cObj->stdWrap($content, $moduleCaller->conf['view.'][$moduleCaller->conf['view'] . '.']['tx_register4cal_fieldset_stdWrap.']);
		$content = str_replace('###TX_REGISTER4CAL_FIELDSET_VALUE###', $fieldset, $content);
				
		return $content;
	}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_fieldset.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_fieldset.php']);
}
?>