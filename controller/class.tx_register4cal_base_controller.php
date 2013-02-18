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
        // get settings
        require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_settings.php');
        $this->settings = tx_register4cal_settings::getInstance();
    }    
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_base_controller.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/controller/class.tx_register4cal_base_controller.php']);
}
?>
