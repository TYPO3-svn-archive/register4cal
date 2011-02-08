<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Thomas Ernst <typo3@thernst.de>
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
 * class.tx_register4cal_register.php
 *
 * Class implementing a register controller
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_base_controller.php');

/**
 * Class to implement a register controller
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_listregister_controller extends tx_register4cal_base_controller {
	/* =========================================================================
	 * Constructor and static getInstance method
	 * ========================================================================= */
	/**
	 * Create an instance of the class while taking care of the different ways
	 * to instanciace classes having constructors with parameters in different
	 * Typo3 versions
	 * @return tx_register4cal_listregister_controller
	 */
	public static function getInstance() {
		$className = 'tx_register4cal_listregister_controller';
		if (t3lib_div::int_from_ver(TYPO3_version) <= 4003000) {
			$className = &t3lib_div::makeInstanceClassName($className);
			$class = new $className();
		} else {
			$class = &t3lib_div::makeInstance($className);
		}
		return $class;
	}

	// Class constructor taken from parent class

	/* =========================================================================
	 * Public methods
	 * ========================================================================= */
	/**
	 * Process actions for event list view
	 * @global tslib_fe $TSFE
	 * @global array $TX_REGISTER4CAL_DATA
	 */
	public function ListViewRegistration() {
		global $TSFE, $TX_REGISTER4CAL_DATA;

		// get data and leave if nothing needs to be processed
		$data = t3lib_div::GParrayMerged($this->prefixId);
		if (!is_array($data)) return;
		if (count($data) == 0) return;

		require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');
		$userId = $TSFE->fe_user->user['uid'];
		foreach ($data as $eventId => $eventData) {
			if (is_array($eventData)) foreach ($eventData as $eventDate => $command) {
					if ($command['register'] == 1) {
						$registration = tx_register4cal_registration_model::getInstance($eventId, $eventDate, $userId);
						if ($registration->getStatus() == 3 || $registration->getStatus() == 4) {
							foreach ($command as $fieldname => $fieldvalue) {
								if (substr($fieldname, 0, 6) == 'FIELD_') {
									$fieldname = substr($fieldname, 6);
									$registration->setUserdefinedFieldValue($fieldname, $fieldvalue);
								}
							}
							$oldStatus = $registration->getStatus();
							if ($registration->register($messages)) {
								$this->sendConfirmationEmail($registration, $oldStatus, $messages);
								$this->sendNotificationEmail($registration, $oldStatus, $messages);
							}
							$TX_REGISTER4CAL_DATA['messages'][$eventId][$eventDate] = $messages;
						}
					} elseif ($command['unregister'] == 1) {
						$registration = tx_register4cal_registration_model::getInstance($eventId, $eventDate, $userId);
						$oldStatus = $registration->getStatus();
						$registration->unregister();
						$this->sendConfirmationEmail($registration, $oldStatus);
						$this->sendNotificationEmail($registration, $oldStatus);
						$this->WaitlistCheck($eventId, $eventDate);
					}
				}
		}
	}

	/**
	 * Show submit button for event list view, if required
	 * @global tslib_fe $TSFE
	 * @global array $TX_REGISTER4CAL_DATA
	 * @return string HTML for submit button in list view
	 */
	public function ListViewRegistration_Submit() {
		global $TSFE, $TX_REGISTER4CAL_DATA;
		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_register_view.php');

		//try {
			$TX_REGISTER4CAL_DATA['ListShowSubmit'] = TRUE;
			// Show submit button if at least one event with registration form is being displayed
			if ($TX_REGISTER4CAL_DATA['ListShowSubmit']) {
				$view = tx_register4cal_register_view::getInstance();
				$view->load('list.submit');
				$TX_REGISTER4CAL_DATA['ListShowSubmit'] = FALSE;
				$content = $view->render();
			} else $content = '';
		//} catch (Exception $ex) {
		//	$content = tx_register4cal_base_view::renderError($ex->getMessage());
		//}
		return $content;
	}

	/**
	 * Show registration form for event in list view if required
	 * @global tslib_fe $TSFE
	 * @global array $TX_REGISTER4CAL_DATA
	 * @param array $event Event record
	 * @return string HTML for registration form
	 */
	public function ListViewRegistration_Event($event) {
		global $TSFE, $TX_REGISTER4CAL_DATA;
		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_register_view.php');

		try {
			// create instance of registration object
			require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');
			$registration = tx_register4cal_registration_model::getInstance($event['uid'], $event['start_date'], $TSFE->fe_user->user['uid']);

			$view = tx_register4cal_register_view::getInstance();
			$view->setRegistration($registration);
			if (isset($TX_REGISTER4CAL_DATA['messages'][$event['uid']][$event['start_date']])) $view->setMessages($TX_REGISTER4CAL_DATA['messages'][$event['uid']][$event['start_date']]);
			switch ($registration->getStatus()) {
				case 0;  // No registration active
				// fall through
				case 1:  // No registration possible at the moment (outside registration period)
				// fall trough
				case 2:  // no registration possible at the moment (event fully booked)
					$content = '';
					break;
				case 3:  // Normal registration is possible
					$view->load('list.registration.enter');
					$TX_REGISTER4CAL_DATA['ListShowSubmit'] = TRUE;
					$content = $view->render();
					break;
				case 4:  // Waitlist enlisting is possible
					$view->load('list.waitlist.enter');
					$TX_REGISTER4CAL_DATA['ListShowSubmit'] = TRUE;
					$content = $view->render();
					break;
				case 5:  // User has already registered
					$view->load('list.registration.alreadyDone');
					$TX_REGISTER4CAL_DATA['ListShowSubmit'] = TRUE;
					$content = $view->render();
					break;
				case 6:  // User has already enlisted on waitlist
					$view->load('list.waitlist.alreadyDone');
					$TX_REGISTER4CAL_DATA['ListShowSubmit'] = TRUE;
					$content = $view->render();
					break;
			}
		} catch (Exception $ex) {
			$content = tx_register4cal_base_view::renderError($ex->getMessage());
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_listregister_controller.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_listregister_controller.php']);
}
?>