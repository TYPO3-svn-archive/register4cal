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
 * class.tx_register4cal_pi1.php
 *
 * Provides plugin to display registrations for user and organizer
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'Registration for Cal-Events' for the 'register4cal' extension.
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_pi1 extends tslib_pibase {
    /* =========================================================================
     * pibase settings
     * ========================================================================= */

    var $prefixId = 'tx_register4cal_pi1';   // Same as class name
    var $scriptRelPath = 'pi1/class.tx_register4cal_pi1.php'; // Path to this script relative to the extension dir.
    var $extKey = 'register4cal'; // The extension key.
    var $pi_checkCHash = true;

    /**
     * The main method of the PlugIn
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     * @return	The content that is displayed on the website
     */
    function main($content, $conf) {
        try {
            $this->conf = $conf;
            $this->pi_setPiVarDefaults();
            $this->pi_initPIflexForm();

            // Read flexform variables
            $pages = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pages');
            $recursive = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive');
            $displayMode = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'displayMode');
            $pidlist = $this->pi_getPidList($pages, $recursive);

            // No storage folder is set --> throw exception
            if (!$pidlist)
                throw new Exception('There is no storage folder set for the plugin. Please notify the site administrator!');
            // No display mode is set --> throw exception
            if (!$displayMode)
                throw new Exception('There is no display mode set for the plugin. Please notify the site administrator!');

            // Call function providing the selected view (or throw exception for unknown viewmode)
            require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_listoutput_controller.php');
            $controller = tx_register4cal_listoutput_controller::getInstance();
            switch ($displayMode) {
                case 2:
                    $content = $controller->EventList($pidlist);
                    break;
                case 3:
                    $content = $controller->ParticipantList($pidlist);
                    break;
                default:
                    // invalid display mode --> throw exception
                    throw new Exception('An invalid display mode is set for the plugin. Please notify the site administrator!');
            }
        } catch (Exception $ex) {
            require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_base_view.php');
            $content = tx_register4cal_base_view::renderError($ex->getMessage());
        }
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Function to create vcard file
     * @param type $content
     * @param type $conf
     * @throws Exception
     */
    function vcard($content, $conf) {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_initPIflexForm();

        // Call function providing the selected view (or throw exception for unknown viewmode)
        require_once(t3lib_extMgm::extPath('register4cal') . 'controller/class.tx_register4cal_singleregister_controller.php');
        $controller = tx_register4cal_singleregister_controller::getInstance();
        $vcard = $controller->SingleVcardDownload();
        if (!$vcard)
            throw new Exception('Function not supported.');
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/pi1/class.tx_register4cal_pi1.php']);
}
?>