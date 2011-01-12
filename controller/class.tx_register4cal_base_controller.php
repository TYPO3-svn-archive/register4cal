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
 * class.tx_register4cal_base_controller.php
 *
 * Base class implementing a controller
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

/**
 * Base class to implement a controller
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_base_controller {
	/* =========================================================================
	 * Protected variables
	 * ========================================================================= */

	/**
	 * Prefix Id for fieldnames´, etc. (same as in tx_register4cal_base_view!)
	 * @var string
	 */
	protected $prefixId = 'tx_register4cal_view';
	/**
	 * Instance of tx_register4cal_settings model, containing extension settings
	 * @var tx_register4cal_settings
	 */
	protected $settings;

	/* =========================================================================
	 * Constructor and static getInstance method
	 * ========================================================================= */
	/**
	 * Create an instance of the class while taking care of the different ways
	 * to instanciace classes having constructors with parameters in different
	 * Typo3 versions
	 * @return tx_register4cal_base_controller
	 */
	public static function getInstance() {
		$className = 'tx_register4cal_base_controller';
		if (t3lib_div::int_from_ver(TYPO3_version) <= 4003000) {
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
		// get settings
		require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_settings.php');
		$this->settings = tx_register4cal_settings::getInstance();
	}

	/**
	 * Perform waitlist check for given event (or all events, no specific event is given)
	 * @param integer $eventId Id of event
	 * @param integer $eventDate Date of event
	 * @return boolean  TRUE: Successful, FALSE: Failed
	 */
	public function WaitlistCheck($eventId=0, $eventDate=0) {
		require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');

		$events = tx_register4cal_registration_model::getEventsWithWaitlistEntries($eventId, $eventDate);
		foreach ($events as $event) {
			$registrations = tx_register4cal_registration_model::getRegistrationsForEvent($event['cal_event_uid'], $event['cal_event_getdate'], 2);
			foreach ($registrations as $registration) {
				$oldStatus = $registration->getStatus();
				if ($registration->waitlistCheck()) {
					//send emails
					$this->sendConfirmationEmail($registration, $oldStatus);
					$this->sendNotificationEmail($registration, $oldStatus);
				} else {
					// eventFillMode "keepRegistrationOrder": Do not bring forward other waitlist entries (probably having less attendees).
					if ($this->settings->eventFillMode == 1) break;
				}
			}
		}
		return TRUE;
	}

	/* =========================================================================
	 * Protected methods - sending emails
	 * ========================================================================= */
	/**
	 * Send confirmation email for registration
	 * @param tx_register4cal_registration $registration Registration
	 * @param integer $oldStatus Registration status prior to registration/unregistration
	 * @param array $messages Messages related to registration
	 * @param boolean $rfu Flag: Action was made by admin using the "register forein user" functionality
	 */
	protected function sendConfirmationEmail($registration, $oldStatus, $messages = Array(), $rfu=FALSE) {
		if (!$this->settings->mailSendConfirmation) return;
		if (!$registration->getUserField('email')) return;

		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_register_view.php');
		$view = tx_register4cal_register_view::getInstance(TRUE);
		$view->setRegistration($registration);
		$view->setMessages($messages);
		switch ($registration->getStatus()) {
			case 5: // user is registered
				if ($oldStatus == 6) {
					$confName = 'email.waitlist.upgrade.confirmation';
				} else {
					$confName = 'email.registration.enter.confirmation';
				}
				break;
			case 6: // user is enlisted to waitlist
				$confName = 'email.waitlist.enter.confirmation';
				break;
			case 3: // user can register (-> has unregistered)
			// fall through
			case 4: // user can enlist to waitlist (-> has unregistered)
				// we need to check the old status to see if he was registered or enlisted to waitlist
				if ($oldStatus == 5) {
					$confName = 'email.registration.cancel.confirmation';
				} elseif
				($oldStatus == 6) {
					$confName = 'email.waitlist.cancel.confirmation';
				}
				break;
		}

		if ($rfu) {
			$rfuConf = $this->settings->formConfig($confName . '_rfu');
			if (isset($rfuConf)) $confName .= '_rfu';
		}

		$view->load($confName);
		$view->sendMail($registration->getUserField('email'));
		$view = null;
	}

	/**
	 * Send notification email for registration
	 * @param tx_register4cal_registration $registration Registration
	 * @param integer $oldStatus Registration status prior to registration/unregistration
	 * @param array $messages Messages related to registration
	 * @param boolean $rfu Flag: Action was made by admin using the "register forein user" functionality
	 */
	protected function sendNotificationEmail($registration, $oldStatus, $messages = Array(), $rfu=FALSE) {
		if (!$this->settings->mailSendNotification) return;

		if ($this->settings->mailAdminAddress) $recipients = t3lib_div::trimExplode(',', $this->settings->mailAdminAddress, 1);
		if ($registration->getEventField('organizer_email')) $recipients[] = $registration->getEventField('organizer_email');

		if (count($recipients) == 0) return;

		require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_register_view.php');
		$view = tx_register4cal_register_view::getInstance(TRUE);
		$view->setRegistration($registration);
		$view->setMessages($messages);
		switch ($registration->getStatus()) {
			case 5: // user is registered
				if ($oldStatus == 6) {
					$confName = 'email.waitlist.upgrade.notification';
				} else {
					$confName = 'email.registration.enter.notification';
				}
				break;
			case 6: // user is enlisted to waitlist
				$confName = 'email.waitlist.enter.notification';
				break;
			case 3: // user can register (-> has unregistered)
			// fall through
			case 4: // user can enlist to waitlist (-> has unregistered)
				// we need to check the old status to see if he was registered or enlisted to waitlist
				if ($oldStatus == 5) {
					$confName = 'email.registration.cancel.notification';
				} elseif
				($oldStatus == 6) {
					$confName = 'email.waitlist.cancel.notification';
				}
				break;
		}

		if ($rfu) {
			$rfuConf = $this->settings->formConfig($confName . '_rfu');
			if (isset($rfuConf)) $confName .= '_rfu';
		}

		$view->load($confName);
		$view->sendMail($recipients);
		$view = null;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_base_controller.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_base_controller.php']);
}
?>
