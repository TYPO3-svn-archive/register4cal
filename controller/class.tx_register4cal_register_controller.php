<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Ernst <typo3@thernst.de>
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
 * Class containing shared functions for registration controllers
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_base_controller.php');
require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_vcard_controller.php');

/**
 * Class containing shared functions for registration controllers
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_register_controller extends tx_register4cal_base_controller {

    /**
     * Perform waitlist check for given event (or all events, no specific event is given)
     * @param integer $eventId Id of event
     * @param integer $eventDate Date of event
     * @return boolean  TRUE: Successful, FALSE: Failed
     */
    public function WaitlistCheck($eventId = 0, $eventDate = 0) {
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
                    if ($this->settings->eventFillMode == 1)
                        break;
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
    protected function sendConfirmationEmail($registration, $oldStatus, $messages = Array(), $rfu = FALSE) {
        if (!$this->settings->mailSendConfirmation)
            return;
        if (!$registration->getUserField('email'))
            return;

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
            if (isset($rfuConf))
                $confName .= '_rfu';
        }

        $view->load($confName);
        $subparts = Array();
        $this->prepareSubparts_OtherUsers($view, $registration, $subparts);        
        $view->sendMail($registration->getUserField('email'),$subparts);
        $view = null;
    }

    /**
     * Send notification email for registration
     * @param tx_register4cal_registration $registration Registration
     * @param integer $oldStatus Registration status prior to registration/unregistration
     * @param array $messages Messages related to registration
     * @param boolean $rfu Flag: Action was made by admin using the "register forein user" functionality
     */
    protected function sendNotificationEmail($registration, $oldStatus, $messages = Array(), $rfu = FALSE) {
        if (!$this->settings->mailSendNotification)
            return;
        if ($this->settings->mailAdminAddress)
            $recipients = t3lib_div::trimExplode(',', $this->settings->mailAdminAddress, 1);
        if ($registration->getEventField('organizer_email'))
            $recipients[] = $registration->getEventField('organizer_email');

        if (count($recipients) == 0)
            return;

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
            if (isset($rfuConf))
                $confName .= '_rfu';
        }

        $attachments = array();
        if ($this->settings->vcardParticipantEnabled) {
            $vcardController = tx_register4cal_vcard_controller::getInstance();
            $attachments[] = array(
                'content' => $vcardController->createVcard($registration),
                'filename' => $this->settings->vcardParticipantFilename,
                'content_type' => 'text/vcard'
            );
        }

        $view->load($confName);
        $subparts = Array();
        $this->prepareSubparts_OtherUsers($view, $registration, $subparts);               
        $view->sendMail($recipients, $subparts, array(), $attachments);
        $view = null;
    }

    /**
     * Bereitet die Subparts zur Anzeige anderer Benutzer vor
     * @param tx_register4cal_register_view $view Instanz der View-Klasse zum Rendering
     * @param tx_register4cal_registration_model $registration Instanz der Regstration-Klasse
     * @param Array $subparts Array mit allen vorbereiteten Subparts
     */
    protected function prepareSubparts_OtherUsers($view, $registration, &$subparts) {
        // Clear subparts
        $subparts['OTHER_USERS_VISIBLE_QUESTION'] = '';
        $subparts['OTHER_USERS_LIST'] = '';

        // Leave if display of other registered users is disabled
        if (!$this->settings->showOtherRegisteredUsers_Enable)
            return;

        // Render checkbox for registration forms
        $subparts['OTHER_USERS_VISIBLE_QUESTION'] = $view->renderSubpart('OTHER_USERS_VISIBLE_QUESTION');

        if ($this->settings->showOtherRegisteredUsers_onlyAfterRegistration && $registration->getStatus() <= 4 && !$registration->userIsOrganizer())
            return;

        // Render list of other users
        $otherUsers = '';
        $otherUserRegistrations = $registration->getRegistrationsFromOtherUsers();
        if (count($otherUserRegistrations) != 0) {
            $view->setRenderDisplayOnly(TRUE);
            foreach ($otherUserRegistrations as $otherUserRegistration) {
                $view->setRegistration($otherUserRegistration);
                $otherUsers .= $view->renderSubpart('OTHER_USER');
            }
            $view->setRegistration($registration);
            $view->setRenderDisplayOnly();
            $view->replaceSubpart('OTHER_USER', $otherUsers);
            unset($subparts['OTHER_USERS_LIST']);
        }
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_register_controller.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_register_controller.php']);
}
?>