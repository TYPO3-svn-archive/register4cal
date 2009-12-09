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
 * class.tx_register4cal_behooks.php
 *
 * Provide the deletion of registrations when the relating event is being deleted.
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
require_once(t3lib_extMgm::extPath('register4cal') . 'classes/class.tx_register4cal_checks.php'); 

/**
 * Delete registrations when the relating event is being deleted by processing the hook 'processDatamap_afterAllOperations'
 *
 * @author 	Thomas Ernst <typo3@thernst.de>
 * @package 	TYPO3
 * @subpackage 	tx_register4cal
 */
 
class tx_register4cal_behooks {
	/***********************************************************************************************************************************************************************
	*
	* Hook from TCEmain to delete registrations on the deletion of the event
	*
	**********************************************************************************************************************************************************************/
	function processDatamap_afterAllOperations ($otherThis) {
		if (is_array($otherThis->cmdmap)) {
			foreach($otherThis->cmdmap as $table => $table_value) {
				if ($table == 'tx_cal_event') {
					foreach($table_value as $uid => $uid_value) {
						foreach($uid_value as $cmd => $cmd_value) {
							if ($cmd == 'delete') {
								//Some entry from table tx_cal_event has been deleted. Deleted linked entries from table tx_register4cal_registrations
								$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_register4cal_registrations', 'cal_event_uid='.intval($uid));
							}
						}
					}
				}
			}
		}
	}
	
	/***********************************************************************************************************************************************************************
	*
	* Hook from TCEmain to check event on the change of an event
	*
	**********************************************************************************************************************************************************************/	
	function processDatamap_postProcessFieldArray($status, &$table, $id, &$fieldArray, $otherThis) {
		if ($table == 'tx_cal_event' && $status == 'update') {
			if (!tx_register4cal_checks::checkAll_Backend($id, $fieldArray, &$error)) {
				$otherThis->log($table, $id, 5,0,1,$error);
				$table = '';
			}
		}
	}
}
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_behooks.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_behooks.php']);
}
 
?>
