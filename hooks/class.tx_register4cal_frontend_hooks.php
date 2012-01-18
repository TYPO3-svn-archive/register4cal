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
 * class.tx_register4cal_frontend_hooks.php
 *
 * Implementing hooks and userfunctions to extend frontend functionality
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
 * Implementing hooks and userfunctions to extend frontend functionality
 *
 * Hooks:
 * - Add registration form to cal single event view
 * - Process registrations in cal list view
 * - Surround cal list view with form-tags if required
 *
 * Userfunctions:
 *
 * @author 	Thomas Ernst <typo3@thernst.de>
 * @package 	TYPO3
 * @subpackage 	tx_register4cal
 */
class tx_register4cal_frontend_hooks {
	/* =========================================================================
	 * Hook from extension cal to add the registration form to single event view
	 * ========================================================================= */
	function preFinishViewRendering() {

	}

	function postSearchForObjectMarker($parent, &$content) {
		//Conditions to display the registration form (first step)
		if ($parent->conf['view'] == 'event' && $parent->cachedValueArray['tx_register4cal_activate']) { /* Single event view and registration enabled... */
			// get piVars from tx_cal_controler
			$data = t3lib_div::_GPmerged('tx_cal_controller');
			if ($data['uid'] != 0) { /* ...displaying an event ... */
				if (strpos($content, 'phpicalendar_event')) { /* ... with event template (not location or organizer ...) */
					try {
						require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_singleregister_controller.php');
						$controller = tx_register4cal_singleregister_controller::getInstance();
						$content .= $controller->SingleEventRegistration();
					} catch (Exception $ex) {
						require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_base_view.php');
						$content .= tx_register4cal_base_view::renderError($ex->getMessage());
					}
				}
			}
		}
	}
	/* =========================================================================
	 * Use hook of cal extension to process registration in list view
	 * ========================================================================= */
	function drawListClass() {
		
	}

	function preListRendering($events, &$class) {
		global $TX_REGISTER4CAL_DATA;
		try {
			require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_listregister_controller.php');
			$controller = tx_register4cal_listregister_controller::getInstance();
			$controller->ListViewRegistration();
			$TX_REGISTER4CAL_DATA['controller'] = $controller;
		} catch (Exception $ex) {
			require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_base_view.php');
			$TX_REGISTER4CAL_DATA['error'] = tx_register4cal_base_view::renderError($ex->getMessage());
		}
	}

	/* =========================================================================
	 * Use hook of cal extension to add form to event list view if required
	 * ========================================================================= */
	function postListRendering(&$content, $events, &$class) {
		global $TX_REGISTER4CAL_DATA;
		//if there are some tx_register4cal_view parts in the content, surround the whole content with a form for the registration
		if (strpos($content, 'tx_register4cal_view') !== FALSE) $content = '<form action="" method="post">' . $content . '</form>';
		unset($TX_REGISTER4CAL_DATA['controller']);
		unset($TX_REGISTER4CAL_DATA['error']);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/hooks/class.tx_register4cal_frontend_hooks.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/hooks/class.tx_register4cal_frontend_hooks.php']);
}
?>
