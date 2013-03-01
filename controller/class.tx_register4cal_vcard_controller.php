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
 * class.tx_register4cal_validation_controller.php
 *
 * Class implementing a controller for the creation of vcards
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'lib/zendvcard/class.tx_register4cal_zendvcard_data.php');
require_once(t3lib_extMgm::extPath('register4cal') . 'lib/zendvcard/class.tx_register4cal_zendvcard_generator.php');

/**
 * Class implementing a controller for the creation of vcards
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_vcard_controller extends tx_register4cal_base_controller {
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
        $className = 'tx_register4cal_vcard_controller';
        if (tx_register4cal_static::getTypo3IntVersion() <= 4003000) {
            $className = &t3lib_div::makeInstanceClassName($className);
            $class = new $className();
        } else {
            $class = &t3lib_div::makeInstance($className);
        }
        return $class;
    }

    /**
     * Create vcard 
     * @param tx_register4cal_registration $registration Registration
     * @param array $fieldmapping Array containing fieldmapping information(field => configuration)
     * @return string
     */
    public function createVcard($registration, $fieldmapping) {
        $vcard = tx_register4cal_zendvcard_data::getInstance();
        
        foreach ($fieldmapping as $field => $value) {
            switch ($field) {
                case 'uid':
                    break;
                case 'fullname':
                    $vcard->fullname = $this->getSingleValue($value, $registration);
                    break;
                case 'title':
                    $vcard->title = $this->getSingleValue($value, $registration);
                    break;
                case 'firstname':
                    $vcard->firstname = $this->getSingleValue($value, $registration);
                    break;
                case 'lastname':
                    $vcard->lastname = $this->getSingleValue($value, $registration);
                    break;
                case 'additionalnames':
                    $vcard->additionalnames = $this->getSingleValue($value, $registration);
                    break;
                case 'nameprefix':
                    $vcard->nameprefix = $this->getSingleValue($value, $registration);
                    break;
                case 'namesuffix':
                    $vcard->namesuffix = $this->getSingleValue($value, $registration);
                    break;
                case 'nickname':
                    $vcard->nickname = $this->getSingleValue($value, $registration);
                    break;
                case 'birthday':
                    $vcard->birthday = $this->getSingleValue($value, $registration);
                    break;
                case 'organization':
                    $vcard->organization = $this->getSingleValue($value, $registration);
                    break;
                case 'department':
                    $vcard->department = $this->getSingleValue($value, $registration);
                    break;
                case 'subdepartment':
                    $vcard->nickname = $this->getSingleValue($value, $registration);
                    break;
                case 'role':
                    $vcard->role = $this->getSingleValue($value, $registration);
                    break;
                case 'revision':
                    $vcard->revision = $this->getSingleValue($value, $registration);
                    break;
                case 'geolocation':
                    $vcard->geolocation = $this->getSingleValue($value, $registration);
                    break;
                case 'mailer':
                    $vcard->mailer = $this->getSingleValue($value, $registration);
                    break;
                case 'timezone':
                    $vcard->timezone = $this->getSingleValue($value, $registration);
                    break;
                case 'phonenumbers.':
                    if (is_array($value)) {
                        foreach ($value as $phonefield => $phonevalue) {
                            if (substr($phonefield, -1) === '.')
                                continue;
                            $phonenumber = $this->getSingleValue($phonevalue, $registration);
                            if (!$phonenumber) continue;
                            $phonetypes = $this->getTypes($value[$phonefield . '.']);
                            $vcard->addPhonenumber($phonenumber, $phonetypes);
                        }
                    }
                    break;
                case 'emails.':
                    if (is_array($value)) {
                        foreach ($value as $emailfield => $emailvalue) {
                            if (substr($emailfield, -1) === '.')
                                continue;
                            $emailaddress = $this->getSingleValue($emailvalue, $registration);
                            if (!$emailaddress) continue;
                            $emailtypes = $this->getTypes($value[$emailfield . '.']);
                            $vcard->addEmail($emailaddress, $emailtypes);
                        }
                    }
                    break;
                case 'urls.':
                    if (is_array($value)) {
                        foreach ($value as $urlfield => $urlvalue) {
                            if (substr($urlfield, -1) === '.')
                                continue;
                            $urladdress = $this->getSingleValue($urlvalue, $registration);
                            if (!$urladdress) continue;
                            $urltypes = $this->getTypes($value[$urlfield . '.']);
                            $vcard->addUrl($urladdress, $urltypes);
                        }
                    }
                    break;
                case 'addresses.':
                    if (is_array($value)) {
                        foreach ($value as $addressfield => $addressvalue) {
                            $address = array(
                                'postofficeaddress' => $this->getSingleValue($addressvalue['postofficeaddress'], $registration),
                                'extendedaddress' => $this->getSingleValue($addressvalue['extendedaddress'], $registration),
                                'street' => $this->getSingleValue($addressvalue['street'], $registration),
                                'city' => $this->getSingleValue($addressvalue['city'], $registration),
                                'state' => $this->getSingleValue($addressvalue['state'], $registration),
                                'zip' => $this->getSingleValue($addressvalue['zip'], $registration),
                                'country' => $this->getSingleValue($addressvalue['country'], $registration),
                                'type' => $this->getTypes($addressvalue['type.']));
                            if (!$address['postofficeaddress'] &&
                                !$address['extendedaddress'] &&
                                !$address['street'] &&
                                !$address['city'] &&
                                !$address['state'] &&
                                !$address['zip'] &&
                                !$address['country'] ) continue;
                            $vcard->addAddress($address);
                        }
                    }
                    break;
            }
        }
        return tx_register4cal_zendvcard_generator::generate($vcard);
    }

    private function getTypes($types) {
        $typearray = array();
        if (is_array($types)) {
            foreach ($types as $typefield => $typevalue) {
                $typevalue = intval($typevalue);
                if ($typevalue == 0)
                    continue;
                $typearray[] = $typefield;
            }
        }
        return $typearray;
    }

    /**
     * Render a single marker
     * @param string $value
     * @param tx_register4cal_registration $registration Registration
     * @return string content for field
     */
    protected function getSingleValue($content, $registration) {
        switch ($content) {
            //case 'LINK' :
            //    // Marker for the registration form
            //    $value = htmlspecialchars($this->pi_getPageLink($TSFE->id));
            //    break;
            //case 'STATUS' :
            //    $value = $this->pi_getLL('label_status_' . intval($registration->getRegistrationField('status')));
            //    break;
            //case 'MAXATTENDEES':
            //    $value = $registration->getEventField('tx_register4cal_maxattendees');
            //    if ($value == 0)
            //        $value = $this->pi_getLL('label_unlimited');
            //    break;
            //case 'NUMATTENDEES':
            //    $value = $registration->getEventField('tx_register4cal_numregistered');
            //    break;
            //case 'NUMFREE':
            //    $maxAttendees = $registration->getEventField('tx_register4cal_maxattendees');
            //    $value = $registration->getEventField('tx_register4cal_maxattendees') == 0 ? $this->pi_getLL('label_unlimited') : $this->registration->getEventField('tx_register4cal_numfree');
            //    break;
            //case 'NUMWAITLIST':
            //    $value = $registration->getEventField('tx_register4cal_numwaitlist');
            //    break;
            default :
                if (preg_match('/EVENT_([A-Z0-9_-])*/', $content)) {
                    // Insert an event field. Special fields have been set during loading the event in tx__register4cal_registration
                    $field = substr($content, 6);
                    $value = $registration->getEventField($field);
                } elseif (preg_match('/LOCATION_([A-Z0-9_-])*/', $content)) {
                    // Insert a field from the location record
                    $field = substr($content, 9);
                    $value = $registration->getLocationField($field);
                } elseif (preg_match('/ORGANIZER_([A-Z0-9_-])*/', $content)) {
                    // Insert a field from the organizer record
                    $field = substr($content, 10);
                    $value = $registration->getOrganizerField($field);
                } elseif (preg_match('/UDEF_([A-Z0-9_-])*/', $content)) {
                    // Insert a field from the user defined fields
                    $field = substr($content, 5);
                    $array = $registration->getUserdefinedField($field);
                    $value = is_array($array) ? $array['value'] : '';
                } elseif (preg_match('/USER_([A-Z0-9_-])*/', $content)) {
                    // Insert an user field. Special fields have been set during loading the user in tx__register4cal_registration
                    $field = substr($content, 5);
                    $value = $registration->getUserField($field);
                    //} elseif (preg_match('/LABEL_([A-Z0-9_-])*/', $content)) {
                    //    // Insert a label field. 
                    //    $fieldname = 'label_' . substr($content, 6);
                    //    $value = $this->pi_getLL($fieldname);
                } else {
                    $value = '';
                }
                break;
        }
        return $value;
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_vcard_controller.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_vcard_controller.php']);
}
?>