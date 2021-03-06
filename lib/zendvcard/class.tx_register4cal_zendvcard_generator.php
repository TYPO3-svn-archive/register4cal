<?php
/* * *************************************************************
 * Copyright 2010 Thomas Schaaf <Thomaschaaf@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Original code is available at:
 * http://code.google.com/p/zendvcard/
 * ************************************************************* */

/**
 * VCard Generator
 * 
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_zendvcard_generator {

    protected static function _renderTimezone($vcardObject) {
        if ($vcardObject->timezone) {
            return 'TZ:' . $vcardObject->timezone . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderMailer($vcardObject) {
        if ($vcardObject->mailer) {
            return 'MAILER:' . $vcardObject->mailer . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderGeolocation($vcardObject) {
        if ($vcardObject->geolocation) {
            return 'GEO:' . $vcardObject->geolocation . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderBirthday($vcardObject) {
        if ($vcardObject->birthday) {
            return 'BDAY:' . $vcardObject->birthday . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderFullname($vcardObject) {
        return 'FN:' . $vcardObject->fullname . tx_register4cal_zendvcard_data::LINEBREAK;
    }

    protected static function _renderName($vcardObject) {
        return 'N:'
                . $vcardObject->lastname . ';'
                . $vcardObject->firstname . ';'
                . $vcardObject->additionalnames . ';'
                . $vcardObject->nameprefix . ';'
                . $vcardObject->namesuffix . tx_register4cal_zendvcard_data::LINEBREAK;
    }

    protected static function _renderVersion($version) {
        return 'VERSION:' . $version . tx_register4cal_zendvcard_data::LINEBREAK;
    }

    protected static function _renderAddress($vcardObject) {

        if ($vcardObject->address) {
            $addressString = '';
            foreach ($vcardObject->address as $address) {
                $addressTypeString = '';
                if ($address['type']) {
                    foreach ($address['type'] as $addressType) {
                        $addressTypeString .= ';' . $addressType;
                    }
                }
                $addressString .= 'ADR' . $addressTypeString . ':'
                        . $address['postofficeaddress'] . ';'
                        . $address['extendedaddress'] . ';'
                        . $address['street'] . ';'
                        . $address['city'] . ';'
                        . $address['state'] . ';'
                        . $address['zip'] . ';'
                        . $address['country'] . tx_register4cal_zendvcard_data::LINEBREAK;
            }
            return $addressString;
        }
    }

    protected static function _renderBegin() {
        return 'BEGIN:VCARD' . tx_register4cal_zendvcard_data::LINEBREAK;
    }

    protected static function _renderTelephone($vcardObject) {
        if ($vcardObject->telephone) {
            $telephoneString = '';
            foreach ($vcardObject->telephone as $telephone) {
                $telephoneTypeString = '';

                if ($telephone['type']) {
                    foreach ($telephone['type'] as $telephoneType) {
                        $telephoneTypeString .= ';' . $telephoneType;
                    }
                }
                $telephoneString .= 'TEL' . $telephoneTypeString . ':' .
                        $telephone['value'] . tx_register4cal_zendvcard_data::LINEBREAK;
            }
            return $telephoneString;
        }
    }

    protected static function _renderEmail($vcardObject) {
        if ($vcardObject->email) {
            $emailString = '';
            foreach ($vcardObject->email as $email) {
                $emailTypeString = '';
                if ($email['type']) {
                    foreach ($email['type'] as $emailType) {
                        $emailTypeString .= ';' . $emailType;
                    }
                }
                $emailString .= 'EMAIL' . $emailTypeString . ':' .
                        $email['value'] . tx_register4cal_zendvcard_data::LINEBREAK;
            }
            return $emailString;
        }
    }

    protected static function _renderTitle($vcardObject) {
        if ($vcardObject->title) {
            return 'TITLE:' . $vcardObject->title . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderRole($vcardObject) {
        if ($vcardObject->role) {
            return 'ROLE:' . $vcardObject->role . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderOrganization($vcardObject) {
        if ($vcardObject->organization ||
                $vcardObject->department ||
                $vcardObject->subdepartment) {
            return 'ORG:'
                    . $vcardObject->organization . ';'
                    . $vcardObject->department . ';'
                    . $vcardObject->subdepartment . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderRevision($vcardObject) {
        if ($vcardObject->revision) {
            return 'REV:' . $vcardObject->revision . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderUrl($vcardObject) {
        if ($vcardObject->url) {
            $urlString = '';
            foreach ($vcardObject->url as $url) {
                $emailTypeString = '';
                if ($url['type']) {
                    foreach ($url['type'] as $emailType) {
                        $emailTypeString .= ';' . $emailType;
                    }
                }
                $urlString .= 'URL' . $emailTypeString . ':' .
                        $url['value'] . tx_register4cal_zendvcard_data::LINEBREAK;
            }
            return $urlString;
        }
    }

    protected static function _renderUid($vcardObject) {
        if ($vcardObject->uid) {
            return 'UID:' . $vcardObject->uid . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderGender($vcardObject) {
        if ($vcardObject->gender) {
            return 'X-GENDER:' . $vcardObject->gender . tx_register4cal_zendvcard_data::LINEBREAK;
        }
    }

    protected static function _renderNickname($vcardObject, $version) {
        if ($version == tx_register4cal_zendvcard_data::VERSION30) {
            if ($vcardObject->nickname) {
                return 'NICKNAME:' . $vcardObject->nickname . tx_register4cal_zendvcard_data::LINEBREAK;
            }
        }
    }

    protected static function _renderInstantmessenger($vcardObject) {
        if ($vcardObject->im) {
            $imString = '';
            foreach ($vcardObject->im as $im) {
                $imTypeString = '';
                if ($im['type']) {
                    foreach ($im['type'] as $imType) {
                        $imTypeString .= ';' . $imType;
                    }
                }
                $imString .= $im['messenger'] . $imTypeString . ':' .
                        $im['value'] . tx_register4cal_zendvcard_data::LINEBREAK;
            }
            return $imString;
        }
    }

    protected static function _renderEnd() {
        return 'END:VCARD';
    }

    public static function generate($vcardObjects = null, $version = tx_register4cal_zendvcard_data::VERSION30) {
        if (!is_array($vcardObjects)) {
            $vcardObjects = array($vcardObjects);
        }

        $vcardString = '';

        foreach ($vcardObjects as $vcardObject) {
            if (!empty($vcardString)) {
                $vcardString .= tx_register4cal_zendvcard_data::LINEBREAK;
            }
            $vcardString .= self::_renderBegin();
            $vcardString .= self::_renderVersion($version);
            $vcardString .= self::_renderFullname($vcardObject);
            $vcardString .= self::_renderName($vcardObject);
            $vcardString .= self::_renderBirthday($vcardObject);
            $vcardString .= self::_renderAddress($vcardObject);
            $vcardString .= self::_renderTelephone($vcardObject);
            $vcardString .= self::_renderEmail($vcardObject);
            $vcardString .= self::_renderInstantmessenger($vcardObject);
            $vcardString .= self::_renderTitle($vcardObject);
            $vcardString .= self::_renderRole($vcardObject);
            $vcardString .= self::_renderOrganization($vcardObject);
            $vcardString .= self::_renderRevision($vcardObject);
            $vcardString .= self::_renderUrl($vcardObject);
            $vcardString .= self::_renderUid($vcardObject);
            $vcardString .= self::_renderNickname($vcardObject, $version);
            $vcardString .= self::_renderGender($vcardObject);
            $vcardString .= self::_renderGeolocation($vcardObject);
            $vcardString .= self::_renderMailer($vcardObject);
            $vcardString .= self::_renderTimezone($vcardObject);
            $vcardString .= self::_renderEnd();
        }

        return $vcardString;
    }

}

?>
