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
 * class.tx_register4cal_admin_controller.php
 *
 * Class to implement a controller for administrative tasks
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_register_controller.php');

/**
 * Class to implement a controller for administrative tasks
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_admin_controller extends tx_register4cal_register_controller {
	/* =========================================================================
	 * protected variables
	 * ========================================================================= */

	/**
	 * Possible actions
	 * @var array Array(cmd=>Array('label'=>label, 'function'=>function))
	 */
	protected $actions = Array();
	/**
	 * Entries for AdminPanel (based on possible actions)
	 * @var array Array(cmd=>label);
	 */
	protected $adminPanelEntries = Array();
	/**
	 * Prepared data for "registerForeignUser" mode
	 * @var array
	 */
	private $rfuData = Array();
	/**
	 * register4cal-piVars
	 * @var array
	 */
	private $piVars = Array();

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
		$className = 'tx_register4cal_admin_controller';
		if (tx_register4cal_static::getTypo3IntVersion() <= 4003000) {
			$className = &t3lib_div::makeInstanceClassName($className);
			$class = new $className();
		} else {
			$class = &t3lib_div::makeInstance($className);
		}
		return $class;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();

		// prepare allowed actions
		$this->actions['checkwaitlist'] = Array('label' => 'label_admin_checkwaitlist', 'function' => 'WaitlistCheck');
		$this->actions['removecancelled'] = Array('label' => 'label_admin_removecancelled', 'function' => 'RemoveCancelledRegistrations');

		if ($this->settings->foreignUserRegistrationEnable) {
			$this->actions['registerForeignUser'] = Array('label' => 'label_admin_registerforeignuser', 'function' => 'RegisterForeignUser');
		}
		// prepare adminPanelEntries array (containing only cmd and label)
		foreach ($this->actions as $cmd => $action)
			$this->adminPanelEntries[$cmd] = $action['label'];
	}

	/* =========================================================================
	 * Public methods - Admin-Panel for frontend
	 * ========================================================================= */
	/**
	 * Process adminpanel functions if required
	 * @return boolean/string FALSE if nothing has been done, otherwise processed command
	 */
	public function AdminPanelProcess() {
		// process commands
		// -> there is no need to check if the user is organizer for the given event, here,
		//    because this is being checked before executing the functions inside
		//    tx_register4cal_registration_model
		$this->piVars = t3lib_div::_GPmerged($this->prefixId);		
		$eventId = intval($this->piVars['uid']);
		$eventDate = intval($this->piVars['getdate']);
		$cmd = $this->piVars['cmd'];
		$retVal = FALSE;
		if ($cmd != '' && $eventId != 0 && $eventDate != 0) {
			if (isset($this->actions[$cmd])) {
				$action = $this->actions[$cmd];
				if (method_exists($this, $action['function'])) {
					if ($this->$action['function']($eventId, $eventDate)) $retVal = $cmd;
				}
			}
		}
		return $retVal;
	}

	/**
	 * Return entries for adminpanel
	 * @return Array Array(cmd => label)
	 */
	public function getAdminPanelEntries() {
		return $this->adminPanelEntries;
	}

	/**
	 * Show "registerForeinUser" form in admin mode
	 * @uses $this->rfuData set in RegisterForeignUser
	 * @return string HTML for "registerForeignUser" form
	 */
	public function ShowRegisterForeignUser() {
		if (!$this->settings->foreignUserRegistrationEnable) return '';
		if (count($this->rfuData) == 0) return '';

		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_register_view.php');
		$view = tx_register4cal_register_view::getInstance();
		$view->load('listOutput.registerForeignUser');
		$view->setRegistration($this->rfuData['registration']);
		$view->setUserList($this->getForeignUserList());
		$view->setMessages($this->rfuData['messages']);
		$content = $view->render();

		return $content;
	}

	/* =========================================================================
	 * Public methods - Administrative methods
	 * ========================================================================= */
	/**
	 * Remove cancelled registrations for given event (or all events, no specific event is given)
	 * @param integer $eventId Id of event
	 * @param integer $eventDate Date of event
	 * @return boolean  TRUE: Successful, FALSE: Failed
	 */
	public function RemoveCancelledRegistrations($eventId=0, $eventDate=0) {
		require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');

		$events = tx_register4cal_registration_model::getEventsWithCancelledEntries($eventId, $eventDate);
		foreach ($events as $event) {
			$registrations = tx_register4cal_registration_model::getRegistrationsForEvent($event['cal_event_uid'], $event['cal_event_getdate'], 3);
			foreach ($registrations as $registration)
				$registration->delete();
		}
		return TRUE;
	}

	/**
	 * Perform registration of foreign user for given event (or all events, no specific event is given)
	 * @param integer $eventId Id of event
	 * @param integer $eventDate Date of event
	 * @return boolean  TRUE: Successful, FALSE: Failed
	 */
	public function RegisterForeignUser($eventId, $eventDate) {
		if ($this->settings->foreignUserRegistrationEnable) {
			$messages = array();
			$userid = $this->piVars['userid'];

			// get registration model object
			require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');
			$registration = tx_register4cal_registration_model::getInstance($eventId, $eventDate, $userid);

			// perform action (if any)
			switch ($this->piVars['subcmd']) {
				case 'register':
					if ($userid == 0) {
						$messages[] = array('label' => 'label_error_nouser', 'type' => 'E');
					} else {
						if ($registration->getStatus() == 3 || $registration->getStatus() == 4) {
							foreach ($this->piVars[$registration->getEventField('uid')][$registration->getEventDate()] as $fieldname => $fieldvalue) {
								if (substr($fieldname, 0, 6) == 'FIELD_') {
									$fieldname = substr($fieldname, 6);
									$registration->setUserdefinedFieldValue($fieldname, $fieldvalue);
								}
							}
							$oldStatus = $registration->getStatus();
							if ($registration->register($messages)) {
								$this->sendConfirmationEmail($registration, $oldStatus, $messages, TRUE);
								$this->sendNotificationEmail($registration, $oldStatus, $messages, TRUE);
							}
						}
					}
					break;
				case 'unregister':
					if ($userid == 0) {
						$messages[] = array('label' => 'label_error_nouser', 'type' => 'E');
					} else {
						if ($registration->getStatus() == 5 || $registration->getStatus() == 6) {
							$oldStatus = $registration->getStatus();
							$registration->unregister();
							$this->sendConfirmationEmail($registration, $oldStatus, TRUE);
							$this->sendNotificationEmail($registration, $oldStatus, TRUE);
						}
					}
					break;
			}

			// prepare data for display
			$this->rfuData = Array(
				'eventId' => $eventId,
				'eventDate' => $eventDate,
				'userid' => $userid,
				'messages' => $messages,
				'registration' => $registration
			);
			return TRUE;
		} else return FALSE;
	}

	private function getForeignUserList() {
		global $TYPO3_DB, $TSFE;

		$userList = Array();
		$select = 'uid, name, email';
		$table = 'fe_users';
		$where = 'uid <> ' . intval($GLOBALS['TSFE']->fe_user->user['uid']);
		if ($this->settings->foreignUserRegistrationAllowOnlyGroups) {
			$groups = t3lib_div::intExplode(',', $this->settings->foreignUserRegistrationAllowOnlyGroups);
			$whereAllow = '';
			foreach ($groups as $group) {
				if ($whereAllow) $whereAllow .= ' OR ';
				$whereAllow .= 'FIND_IN_SET(\'' . intval($group) . '\', usergroup) > 0';
			}
			$where .= ' AND (' . $whereAllow . ')';
		}
		if ($this->settings->foreignUserRegistrationDenyGroups) {
			$groups = t3lib_div::intExplode(',', $this->settings->foreignUserRegistrationDenyGroups);
			$whereDeny = '';
			foreach ($groups as $group) {
				if ($whereDeny) $whereDeny .= ' AND ';
				$whereDeny .= 'FIND_IN_SET(\'' . intval($group) . '\', usergroup) = 0';
			}
			$where .= ' AND (' . $whereDeny . ')';
		}
		$where .= $TSFE->cObj->enableFields($table);
		$result = $TYPO3_DB->exec_SELECTquery($select, $table, $where);
		while ($row = $TYPO3_DB->sql_fetch_assoc($result)) {
			$userList[$row['uid']] = $row['name'] . ' (' . $row['email'] . ')';
		}

		return $userList;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_admin_controller.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_admin_controller.php']);
}
?>