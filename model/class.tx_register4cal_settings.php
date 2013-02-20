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
 * class.tx_register4cal_settings.php
 *
 * Settings for extension register4cal
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');

/**
 * Provides settings for register4cal (singleton class)
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_settings implements t3lib_Singleton {
	/* =========================================================================
	 * Public variables used to access settings from other classes
	 * ========================================================================= */

	/**
	 * Name of template file
	 * @return string
	 */
	public $templateFile;
	/**
	 * Date format
	 * @var string 
	 */
	public $dateFormat;
	/**
	 * Time format
	 * @var string 
	 */
	public $timeFormat;
	/**
	 * Flag: Waitlist disabled
	 * @var integer [0|1] 
	 */
	public $disableWaitlist;
	/**
	 * Flag: Unregistering disables
	 * @var integer [0|1]
	 */
	public $disableUnregister;
	/**
	 * Flag: Enlist on waitlist if not enough places for normal registration are available
	 * @var integer [0|1]
	 */
	public $useWaitlistIfNotEnoughPlaces;
	/**
	 * Flag: Fill-Mode for events
	 * @var integer [1|2]
	 */
	public $eventFillMode;
	/**
	 * Flag: Keep records from canceled registrations
	 * @var integer [0|1]
	 */
	public $keepUnregisteredEntries;
	/**
	 * List of user id's which are registration admins. They will be treated like
	 * event organizers for any event
	 * @var string
	 */
	public $adminUsers;
	/**
	 * Pid of page to use for single event view
	 * @var integer 
	 */
	public $singleEventPid;
	/**
	 * Email-Settings: List of Email addresses which should receive any notification email
	 * @var string
	 */
	public $mailAdminAddress;
	/**
	 * Email-Settings: Email address of sender
	 * @var string
	 */
	public $mailSenderAddress;
	/**
	 * Email-Settings: Name of sender
	 * @var string
	 */
	public $mailSenderName;
	/**
	 * Email-Settings: Flag: Send confirmation emails (to registering user)
	 * @var integer [0|1]
	 */
	public $mailSendConfirmation;
	/**
	 * Email-Settings: Flag: Send notification emails (to organizer)
	 * @var integer [0|1]
	 */
	public $mailSendNotification;        
        
	/**
	 * NeedLoginForm: Flag: Form disabled?
	 * @var integer [0|1]
	 */
	public $needLoginFormDisable;
	/**
	 * NeedLoginForm: Pid of page with normal login form
	 * @var integer
	 */
	public $needLoginFormLoginPid;
	/**
	 * NeedLoginForm: Parameter name which needs to contain the URL to return
	 * to after successful normal login
	 * @var string
	 */
	public $needLoginFormLoginReturnParam;
	/**
	 * NeedLoginForm: Pid of page with OntimeAccount login form
	 * @var integer
	 */
	public $needLoginFormOnetimeAccountPid;
	/**
	 * NeedLoginForm: Parameter name which needs to contain the URL to return
	 * to after successful OntimeAccount login
	 * @var string
	 */
	public $needLoginFormOnetimeAccountReturnParam;
	/**
	 * Foreign User Registration: Flag: Enabled?
	 * @var integer [0|1]
	 */
	public $foreignUserRegistrationEnable;
	/**
	 * Foreign User Registration: List of groups which should be the only ones
	 * of which users should be chooseable for foreign user registration
	 * @var string
	 */
	public $foreignUserRegistratationAllowOnlyGroups;
	/**
	 * Foreign User Registration: List of groups of which the users must not be
	 * chooseable for foreign user registration
	 * @var string
	 */
	public $foreignUserRegistrationDenyGroups;
	
	/**
	 * Flag: Enable the display of other registered users 
	 * @var integer 
	 */
	public $showOtherRegisteredUsers_Enable;
		
	/**
	 * Flag: Display other registered users only after an user has registered
	 * @var integer 
	 */
	public $showOtherRegisteredUsers_onlyAfterRegistration;
	
	/**
	 * Flag: Include own registration when displaying other registered users data
	 * @var integer 
	 */
	public $showOtherRegisteredUsers_includeOwnRegistration;
	
	/**
	 * Flag: Include waitlist registrations when displaying other registered users data
	 * @var integer 
	 */
	public $showOtherRegisteredUsers_includeWaitlist;
	
	/**
	 * Flag: Include cancelled registrations when displaying other registered users data
	 * @var integer 
	 */
	public $showOtherRegisteredUsers_includeCancelled;
	/**
	 * Configuration subset with all forms configuration
	 * @var array
	 */
	private $forms = array();

        /**
         * VCard: VCard for participant enabled
         * @var integer [0|1]
         */
        public $vcardParticipantEnabled;
        
        /**
         * VCard: Filename for attached vcard-file
         * @var string 
         */
        public $vcardParticipantFilename;
        
        /**
         * VCard: Configuration subset with vcard fieldmapping
         * @var array
         */
        public $vcardParticipantFieldmapping = array();
        
        /**
         * TypeNum for vcard download page
         * @var type 
         */
        public $vcardParticipantPageTypeNum = 0;
        
	/* =========================================================================
	 * Constructor and static getInstance method
	 * ========================================================================= */
	/**
	 * Create an instance of the class.
	 * Important: This class is a singleton class
	 * @return tx_register4cal_settings
	 */
	public static function getInstance() {
		return t3lib_div::makeInstance('tx_register4cal_settings');
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->readSettings();
		$this->checkVitalSettings();
	}

	/* =========================================================================
	 * Public methods
	 * ========================================================================= */
	/**
	 * Return form config part. Form config parts are defined in TypoScript
	 * below plugin.tx_register4cal_pi1.forms.
	 *
	 * For compatibility reasons function reads default values defined in
	 * plugin.tx_register4cal_pi1.forms.default and adds these to the settings.
	 * This functionality will be removed later on.
	 *
	 * @param string $configName Name of part to return.
	 * @return Array Array with all elements
	 */
	public function formConfig($configName) {
		$conf = $this->forms;
		$confArray = explode('.', $configName);
		foreach ($confArray as $confPart) {
			$conf = $conf[$confPart . '.'];
		}
		if (!is_array($conf)) $conf = Array();

		// Start of compatibility part =========================================
		if (isset($this->forms['default.'])) {
			$default = $this->forms['default.'];
			if (!is_array($default)) $default = Array();
			$conf = t3lib_div::array_merge_recursive_overrule($default, $conf);
		}
		// End of compatibility part ===========================================
		return $conf;
	}

	/* =========================================================================
	 * Private methods
	 * ========================================================================= */
	/**
	 * Read all settings from TS Config and store them in the variables of this class
	 * In some cases, settings have changed from version 0.7.0 on. In this cases
	 * old settings are being read, if new settings do not exist. This will be removed
	 * after implementing a configuration checker in a backend module
	 */
	private function readSettings() {
		global $TSFE, $TYPO3_DB;

		if (!isset($TSFE->tmpl->setup['plugin.']['tx_register4cal_pi1.'])) throw new Exception('Configuration error: No register4cal configuration found. Are you sure you included the static template for this extension?');

		$tsconf = $TSFE->tmpl->setup['plugin.']['tx_register4cal_pi1.'];
		
		$this->templateFile = isset($tsconf['templateFile']) ? $tsconf['templateFile'] : $tsconf['template'];

		$this->dateFormat = $tsconf['dateformat'];

		$this->timeFormat = $tsconf['timeformat'];

		$this->disableWaitlist = $this->validateFlag($tsconf['disableWaitlist']);

		$this->disableUnregister = $this->validateFlag($tsconf['disableUnregister']);

		$this->useWaitlistIfNotEnoughPlaces = $this->validateFlag($tsconf['useWaitlistIfNotEnoughPlaces']);

		$temp = isset($tsconf['eventFillMode']) ? $tsconf['eventFillMode'] : $tsconf['waitlistMode'];
		$this->eventFillMode = $this->validateValue($temp, '1,2', 1);

		$this->keepUnregisteredEntries = $this->validateFlag($tsconf['keepUnregistered']);

		$temp = isset($tsconf['adminUsers']) ? $tsconf['adminUsers'] : $tsconf['view.']['adminUsers'];
		$this->adminUsers = $TYPO3_DB->cleanIntList($temp);

		$temp = isset($tsconf['singleEventPid']) ? $tsconf['singleEventPid'] : $tsconf['view.']['eventViewPid'];
		$this->singleEventPid = intval(isset($tsconf['singleEventPid']) ? $tsconf['singleEventPid'] : $tsconf['view.']['eventViewPid']);

		$this->foreignUserRegistrationEnable = $this->validateFlag($tsconf['foreignUserRegistration.']['enable']);

		$temp = $tsconf['foreignUserRegistration.']['allowOnlyGroups'];
		$this->foreignUserRegistrationAllowOnlyGroups = $TYPO3_DB->cleanIntList($temp);

		$temp = $tsconf['foreignUserRegistration.']['denyGroups'];
		$this->foreignUserRegistrationDenyGroups = $TYPO3_DB->cleanIntList($temp);

		$temp = isset($tsconf['needLoginForm.']['disable']) ? $tsconf['needLoginForm.']['disable'] : $tsconf['disableNeedLoginForm'];
		$this->needLoginFormDisable = $this->validateFlag($temp);

		$temp = isset($tsconf['needLoginForm.']['loginpid']) ? $tsconf['needLoginForm.']['loginpid'] : $tsconf['loginpid'];
		$this->needLoginFormLoginPid = intval($temp);

		$this->needLoginFormLoginReturnParam = isset($tsconf['needLoginForm.']['loginreturnparam']) ? $tsconf['needLoginForm.']['loginreturnparam'] : $tsconf['loginreturnparam'];

		$temp = isset($tsconf['needLoginForm.']['onetimepid']) ? $tsconf['needLoginForm.']['onetimepid'] : $tsconf['onetimepid'];
		$this->needLoginFormOnetimeAccountPid = intval($temp);

		$this->needLoginFormOnetimeAccountReturnParam = isset($tsconf['needLoginForm.']['onetimereturnparam']) ? $tsconf['needLoginForm.']['onetimereturnparam'] : $tsconf['onetimereturnparam'];

		$temp = isset($tsconf['emails.']['sendConfirmation']) ? $tsconf['emails.']['sendConfirmation'] : $tsconf['emails.']['sendConfirmationMail'];
		$this->mailSendConfirmation = $this->validateFlag($temp);

		$temp = isset($tsconf['emails.']['sendNotification']) ? $tsconf['emails.']['sendNotification'] : $tsconf['emails.']['sendNotificationMail'];
		$this->mailSendNotification = $this->validateFlag($temp);

		$this->mailAdminAddress = $tsconf['emails.']['adminAddress'];

		$this->mailSenderAddress = $tsconf['emails.']['senderAddress'];

		$this->mailSenderName = $tsconf['emails.']['senderName'];

		$temp = $tsconf['showOtherRegisteredUsersAtRegistration.']['enable'];
		$this->showOtherRegisteredUsers_Enable = $this->validateFlag($temp, 0);
		
		$temp = $tsconf['showOtherRegisteredUsersAtRegistration.']['onlyAfterRegistration'];
		$this->showOtherRegisteredUsers_onlyAfterRegistration = $this->validateFlag($temp, 0);
		
		$temp = $tsconf['showOtherRegisteredUsersAtRegistration.']['includeOwnRegistration'];
		$this->showOtherRegisteredUsers_includeOwnRegistration = $this->validateFlag($temp, 0);
		
		$temp = $tsconf['showOtherRegisteredUsersAtRegistration.']['includeWaitlist'];
		$this->showOtherRegisteredUsers_includeWaitlist = $this->validateFlag($temp, 0);
		
		$temp = $tsconf['showOtherRegisteredUsersAtRegistration.']['includeCancelled'];
		$this->showOtherRegisteredUsers_includeCancelled = $this->validateFlag($temp, 0);
		
		$this->forms = $tsconf['forms.'];
                
                $temp = $tsconf['vcardParticipant.']['enable'];                
                $this->vcardParticipantEnabled = $this->validateFlag($temp, 0);
                
                $temp = $tsconf['vcardParticipant.']['filename'];
                $this->vcardParticipantFilename = $temp ? $temp : 'participant.vcf';
                
                $temp = $tsconf['vcardParticipant.']['typeNum'];
                $this->vcardParticipantPageTypeNum = intval($temp);
                
                $this->vcardParticipantFieldmapping = $tsconf['vcardParticipant.']['fieldmapping.'];
	}

	/**
	 * Check if vital settings have been made and throw exception if this is not
	 * the case
	 */
	private function checkVitalSettings() {
		if (!$this->templateFile) throw new Exception('Configuration error: No template file set!');
		if ($this->singleEventPid == 0) throw new Exception('Configuration error: singleEventPid not set!');
		if ($this->mailSendNotification || $this->mailSendConfirmation) {
			if ($this->mailSenderName == '' )throw new Exception('Configuration error: emails.senderName not set!');
			if ($this->mailSenderAddress == '' )throw new Exception('Configuration error: emails.senderAddress not set!');
		}
	}

	/**
	 * Validate a flag value.
	 * @param integer $value Value to validate
	 * @param integer $default Default if $value is invalid
	 * @return integer Validated value [0|1]
	 */
	private function validateFlag($value, $default=0) {
		$value = intval($value);
		if ($value !== 0 && $value !== 1) $value = $this->validateFlag($default);
		return $value;
	}

	/**
	 * Validate a numeric value
	 * @param integer $value Value to validate
	 * @param string $allowedValues List of allowed values
	 * @param integer $default Default value to use of $value is not valid
	 * @return integer Validated value
	 */
	private function validateValue($value, $allowedValues, $default) {
		$value = intval($value);
		if (!t3lib_div::inList($allowedValues, $value)) {
			if (!t3lib_div::inList($allowedValues, $default)) throw new Exception('Value validation failed. Given default value is not a valid value!');
			$value = $default;
		}
		return $value;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/model/class.tx_register4cal_settings.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/model/class.tx_register4cal_settings.php']);
}
?>
