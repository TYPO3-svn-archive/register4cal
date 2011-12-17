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
 * Class implementing a register controller for registering from event single view
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_base_controller.php');

/**
 * Class implementing a register controller for registering from event single view
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_singleregister_controller extends tx_register4cal_base_controller {
    /* =========================================================================
     * Constructor and static getInstance method
     * ========================================================================= */

    /**
     * Create an instance of the class while taking care of the different ways
     * to instanciace classes having constructors with parameters in different
     * Typo3 versions
     * @return tx_register4cal_singleregister_controller
     */
    public static function getInstance() {
        $className = 'tx_register4cal_singleregister_controller';
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
     * Handle complete registration from single event view
     * @global tslib_fe $TSFE
     * @return string HTML-Content to add to single event view
     */
    public function SingleEventRegistration() {
        global $TSFE;
        require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_register_view.php');
        require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_registration_model.php');

        try {
            // get piVars from cal and register4cal
            $calPiVars = t3lib_div::_GPmerged('tx_cal_controller');
            $r4cPiVars = t3lib_div::_GPmerged($this->prefixId);

            // extract required variables
            $eventId = intval($calPiVars['uid']);
            if (!isset($calPiVars['getdate']) && isset($calPiVars['year'])) {
                // Compatibility to cal 1.3: instead of getdate now year, month and day are supplied
                $eventDate = intval($calPiVars['year'] . $calPiVars['month'] . $calPiVars['day']);
            } else {
                $eventDate = intval($calPiVars['getdate']);
            }
            if ($calPiVars['tx_register4cal_cmd'] == 'registerforeinguser') {
                $userId = intval($r4cPiVars['userid']);
            } else {
                $userId = intval($TSFE->fe_user->user['uid']);
            }

            // show form "NeedLoginForm" if no user has logged in and the form should be shown
            if (intval($TSFE->fe_user->user['uid']) == 0) {

                if ($this->settings->needLoginFormDisable == 0) {
                    $registration = tx_register4cal_registration_model::getInstance($eventId, $eventDate, 0);
                    $status = $registration->getStatus();
                    if ($status == 3 || $status == 4) {
                        $view = tx_register4cal_register_view::getInstance();
                        $view->setRegistration($registration);
                        $view->load('single.needLogin');
                        $content = $view->render();
                        return $content;
                    } else
                        return;
                } else {
                    // NeedLoginForm should be hidden, login not possible --> Display nothing!
                    return '';
                }
            }

            // create instance of registration object			
            $registration = tx_register4cal_registration_model::getInstance($eventId, $eventDate, $userId);
            // create instance of view object
            $view = tx_register4cal_register_view::getInstance();

            // process actions
            if ($r4cPiVars['cmd'] == 'register' && ($registration->getStatus() == 3 || $registration->getStatus() == 4)) {
                foreach ($r4cPiVars[$registration->getEventField('uid')][$registration->getEventDate()] as $fieldname => $fieldvalue) {
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
                $view->setMessages($messages);
            } elseif ($r4cPiVars['cmd'] == 'unregister' && ($registration->getStatus() == 5 || $registration->getStatus() == 6)) {
                $oldStatus = $registration->getStatus();
                $registration->unregister();
                $this->sendConfirmationEmail($registration, $oldStatus);
                $this->sendNotificationEmail($registration, $oldStatus);
                $this->WaitlistCheck($eventId, $eventDate);
            }

            // prepare output
            $view->setRegistration($registration);
            switch ($registration->getStatus()) {
                case 0;  // No registration active
                // fall through
                case 1:  // No registration possible at the moment (outside registration period)
                // fall trough
                case 2:  // no registration possible at the moment (event fully booked)
                    $content = '';
                    break;
                case 3:  // Normal registration is possible
                    $view->load('single.registration.enter');
                    $content = $view->render();
                    break;
                case 4:  // Waitlist enlisting is possible
                    $view->load('single.waitlist.enter');
                    $content = $view->render();
                    break;
                case 5:  // User has already registered
                    $view->load('single.registration.alreadyDone');
                    $content = $view->render();
                    break;
                case 6:  // User has already enlisted on waitlist
                    $view->load('single.waitlist.alreadyDone');
                    $content = $view->render();
                    break;
                case 7:  // User has registered and registration period is over
                    $view->load('single.registration.over');
                    $content = $view->render();
                    break;
                case 8:  // User has enlisted on waitlist and registration period is over
                    $view->load('single.waitlist.over');
                    $content = $view->render();
                    break;
                case 9:  // User has registered and event has started
                    $view->load('single.registration.running');
                    $content = $view->render();
                    break;
                case 10:  // User has enlisted on waitlist and event has started
                    $view->load('single.waitlist.running');
                    $content = $view->render();
                    break;
                case 11:  // User has registered and event is finished
                    $view->load('single.registration.finished');
                    $content = $view->render();
                    break;
                case 12:  // User has enlisted on waitlist and event is finished
                    $view->load('single.waitlist.finished');
                    $content = $view->render();
                    break;
            }
        } catch (Exception $ex) {
            $content = tx_register4cal_base_view::renderError($ex->getMessage());
        }
        return $content;
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_singleregister_controller.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_singleregister_controller.php']);
}
?>
