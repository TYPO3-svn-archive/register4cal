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
 * class.tx_register4cal_listoutput_controller.php
 *
 * Class to implement a controller to show lists
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_base_controller.php');

/**
 * Class to implement a controller to show lists
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_listoutput_controller extends tx_register4cal_base_controller {
	/* =========================================================================
	 * Constructor and static getInstance method
	 * ========================================================================= */
	/**
	 * Create an instance of the class while taking care of the different ways
	 * to instanciace classes having constructors with parameters in different
	 * Typo3 versions
	 * @return tx_register4cal_listoutput_controller
	 */
	public static function getInstance() {
		$className = 'tx_register4cal_listoutput_controller';
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
	 * Public methods - Showing lists
	 * ========================================================================= */

	public function EventList($pidlist) {
		global $TSFE;
		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_listoutput_view.php');
		try {

			$subparts = Array(
				'###ITEMS###' => '',
				'###NOITEMS###' => '',
				'###NOLOGIN###' => '',
			);

			$view = tx_register4cal_listoutput_view::getInstance();
			$view->load('listOutput.events');

			if ($TSFE->fe_user->user['uid'] == 0) {
				$subparts['###NOLOGIN###'] = $view->renderSubpart('NOLOGIN');
			} else {
				require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');
				$registrations = tx_register4cal_registration_model::getRegistrationsForUser($pidlist);
				if (count($registrations) == 0) {
					$subparts['###NOITEMS###'] = $view->renderSubpart('NOITEMS');
				} else {
					foreach ($registrations as $registration) {
						$view->setRegistration($registration);
						$subparts['###ITEMS###'] .= $view->renderSubpart('ITEMS');
					}
				}
			}

			$content = $view->render($subparts);
		} catch (Exception $ex) {
			$content = tx_register4cal_base_view::renderError($ex->getMessage());
		}
		return $content;
	}

	public function ParticipantList($pidlist) {
		global $TSFE;
		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_listoutput_view.php');
		require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_admin_controller.php');

		try {

			$admin = tx_register4cal_admin_controller::getInstance();
			$command = $admin->AdminPanelProcess();
			if ($command == 'registerForeignUser') return $admin->ShowRegisterForeignUser();
			
			$subparts = Array(
				'###NOEVENTS###' => '',
				'###EVENTENTRY###' => '',
				'###ITEMS###' => '',
				'###EVENTSPACER###' => '',
				'###NOITEMS###' => '',
				'###NOLOGIN###' => '',
			);

			$view = tx_register4cal_listoutput_view::getInstance();
			$view->setAdminPanelEntries($admin->getAdminPanelEntries());
			$view->load('listOutput.attendees');

			if ($TSFE->fe_user->user['uid'] == 0) {
				$subparts['###NOLOGIN###'] = $view->renderSubpart('NOLOGIN');
			} else {
				require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');
				$registrations = tx_register4cal_registration_model::getRegistrationsForOrganizer($pidlist);
				if (count($registrations) == 0) {
					$subparts['###NOEVENTS###'] = $view->renderSubpart('NOEVENTS');
				} else {
					$list = '';

					foreach ($registrations as $eventDate => $eventIds) {
						foreach ($eventIds as $eventId => $eventRegistrations) {
							$view->setRegistration($eventRegistrations[0]);
							if (!$list == '') $list .= $view->renderSubpart('EVENTSPACER');

							$list .= $view->renderSubpart('EVENTENTRY');
							if ($eventRegistrations[0]->getUserField('uid')) {
								foreach ($eventRegistrations as $registration) {
									$view->setRegistration($registration);
									$list .= $view->renderSubpart('ITEMS');
								}
							} else {
								$list .= $view->renderSubpart('NOITEMS');
							}
						}
					}
					$subparts['###ITEMS###'] .= $list;
				}
			}
			$content = $view->render($subparts);
		} catch (Exception $ex) {
			$content = tx_register4cal_base_view::renderError($ex->getMessage());
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_listoutput_controller.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_listoutput_controller.php']);
}
?>