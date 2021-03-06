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
 * class.tx_register4cal_backend_hooks.php
 *
 * Implementing hooks and userfunctions to extend backend functionality
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

/**
 * Implementing hooks and userfunctions to extend backend functionality
 *
 * Hooks:
 * - delete all registrations after the deletion of the related event
 * - validate additional tx_register4cal_fields in the event record
 * Userfunctions:
 * - show userdefined fields with registration in backend
 *
 * @author 	Thomas Ernst <typo3@thernst.de>
 * @package 	TYPO3
 * @subpackage 	tx_register4cal
 */
class tx_register4cal_backend_hooks {
	// TODO SEV9 Version 0.7.1 Show registration count etc, on Backend for event?
	
	/* =========================================================================
	 * Hook from TCEmain to delete registrations on the deletion of the event
	 * ========================================================================= */
	public function processDatamap_afterAllOperations($otherThis) {
		if (is_array($otherThis->cmdmap)) {
			foreach ($otherThis->cmdmap as $table => $table_value) {
				if ($table == 'tx_cal_event') {
					foreach ($table_value as $uid => $uid_value) {
						foreach ($uid_value as $cmd => $cmd_value) {
							if ($cmd == 'delete') {
								//Some entry from table tx_cal_event has been deleted. Deleted linked entries from table tx_register4cal_registrations
								$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_register4cal_registrations', 'cal_event_uid=' . intval($uid));
							}
						}
					}
				}
			}
		}
	}

	/* =========================================================================
	 * Hook from TCEmain to check event after changes
	 * ========================================================================= */
	public function processDatamap_postProcessFieldArray($status, &$table, $id, &$fieldArray, $parent) {
		if ($table == 'tx_cal_event' && $status == 'update') {
			require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_validation_controller.php');
			if (!tx_register4cal_validation_controller::onBackendEventUpdate($id, $fieldArray, $error)) {
				$parent->log($table, $id, 5, 0, 1, $error);
				$table = '';
			}
		}
	}

	/* =========================================================================
	 * Userfunc to display the additional registration data in the backend
	 * ========================================================================= */
	/**
	 * Render userfields for backend display
	 * @param array $PA item data
	 * @param mixed $fobj ??
	 * @return string Userdefined fields, rendered for backend display
	 */
	public function additionalDataForBackend($PA, $fobj) {
		$fieldsarray = unserialize($PA['itemFormElValue']);
		$additionalFields = '';
		if (is_array($fieldsarray)) {
			foreach ($fieldsarray as $name => $field) {
				$additionalFields .= '<tr><td width= 100px;><b>' . htmlspecialchars($field['conf']['caption']) . '</b></td>' .
						'<td>' . htmlspecialchars($field['value']) . '</td></tr>';
			}
			$additionalFields = '<table width=100% border = 1 style="border:1px solid black;border-collapse:collapse;">' . $additionalFields . '</table>';
		}
		return $additionalFields;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/hooks/class.tx_register4cal_backend_hooks.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/hooks/class.tx_register4cal_backend_hooks.php']);
}
?>
