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
 * class.tx_register4cal_register_view.php
 *
 * View class for registrations
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_base_view.php');

/**
 * View class for registrations
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_register_view extends tx_register4cal_base_view {
	/* =========================================================================
	 * Private variables
	 * ========================================================================= */

	protected $messages = Array();
	/**
	 * Flag: Render in display mode only (no Input mode)
	 * @var boolean 
	 */
	protected $renderDisplayOnly = FALSE;
	/**
	 * List of users for user selection field
	 * @var Array Array(uid=>name) 
	 */
	protected $userList = Array();

	/* =========================================================================
	 * Constructor and static getInstance() methid
	 * ========================================================================= */
	/**
	 * Create an instance of the class while taking care of the different ways
	 * to instanciace classes having constructors with parameters in different
	 * Typo3 versions
	 * @param boolean $renderDisplayOnly @see __construct
	 * @return tx_register4cal_register_view
	 */
	public static function getInstance($renderDisplayOnly = FALSE) {
		$className = 'tx_register4cal_register_view';
		if (t3lib_div::int_from_ver(TYPO3_version) <= 4003000) {
			$className = &t3lib_div::makeInstanceClassName($className);
			$class = new $className($renderDisplayOnly);
		} else {
			$class = &t3lib_div::makeInstance($className, $renderDisplayOnly);
		}
		return $class;
	}

	/**
	 * Class constructor
	 * @global tslib_fe $TSFE
	 * @param boolean $renderDisplayOnly Flag: Render display only (even if status requires input rendering)
	 */
	public function __construct($renderDisplayOnly = FALSE) {
		parent::__construct();
		$this->renderDisplayOnly = $renderDisplayOnly;
	}

	/* =========================================================================
	 * Properties (get and set)
	 * ========================================================================= */
	/**
	 * Set array of messages to use
	 * @param array $messages Messages to use (Array(Array('label'=>llKey,'type'=>type))
	 */
	public function setMessages($messages) {
		$this->messages = $messages;
	}

	/**
	 * Sets array of users to use for user selection field
	 * @param array $userList Array(uid=>name);
	 */
	public function setUserList($userList) {
		$this->userList = $userList;
	}

	/* =========================================================================
	 * Private methods
	 * ========================================================================= */
	/**
	 * Render a single marker
	 * @param	string		$singleMarker: marker string, without ###
	 * @param	array		$conf: Relevant configuration array, containing the stdWrap settings for the elements
	 * @param	string		$mode: Mode to render
	 * @return 	string		content to replace the marker
	 */
	protected function renderSingleMarker($singleMarker) {
		global $TSFE;
		switch ($singleMarker) {
			// Start of compatiblity code ======================================
			case 'ERRORMESSAGE' :
			// fall through
			// End of compatiblity code ========================================
			case 'MESSAGES':
				// Render messages
				$messages = '';
				foreach ($this->messages as $message) {
					$label = $this->pi_getLL($message['label'], '(unknown error key: "' . $message['label'] . '")');
					$wrap = 'messagetype_' . strtolower($message['type']);
					$messages .= $this->applywrap($wrap, $label);
				}
				$marker = $this->applyWrap('messages', $messages);
				break;
			case 'FIELDS' :
				// Render the userfields
				require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_userdefinedfield_view.php');
				$fieldsContent = '';
				$fields = array_keys($this->registration->getUserdefinedFields());
				foreach ($fields as $fieldname) {
					$fieldClass = tx_register4cal_userdefinedfield_view::getInstance($fieldname, $this->registration, $this->renderDisplayOnly);
					$fieldContent = $this->applyWrap('userfield', $fieldClass->render());
					$captionMarker = Array('###CAPTION###' => htmlspecialchars($fieldClass->getCaption()));
					$fieldsContent .= $this->cObj->substituteMarkerArray($fieldContent, $captionMarker);
				}

				// Start of compatibility part =================================
				if (count($this->settings->formConfig('default')) != 0 && ($this->registration->getStatus() == 3 || $this->registration->getStatus() == 4)) {
					$hiddenFields .= $this->getHiddenFields(1, 'register');
					$fieldsContent .= $this->applyWrap('submitbutton', $hiddenFields);
				}
				// End of compatibility part ===================================

				$marker = $this->applyWrap('fields', $fieldsContent);
				break;
			case 'EVENT_CHECKBOX':
				if ($this->registration->getStatus() == 3 || $this->registration->getStatus() == 4) {
					$cmd = 'register';
				} else {
                                        $cmd = 'unregister';
				}
				$value = '<input type="checkbox" name="' . $this->prefixId . '[' . $this->registration->getEventField('uid') . '][' . $this->registration->getEventDate() . '][' . $cmd . ']" value="1" />';
				$marker = $this->applyWrap('eventcheckbox', $value);
				break;
			case 'ONETIMEACCOUNTLINK' :
				// Link to the onetime account display
				$params = Array(
					$this->settings->needLoginFormOnetimeAccountReturnParam => t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->pi_getPageLink($TSFE->id, '', $this->getCalParams()),
				);
				$value = $this->pi_getPageLink($this->settings->needLoginFormOnetimeAccountPid, '', $params);
				$marker = $this->applyWrap('onetimeaccountlink', $value);
				break;
			case 'LOGINLINK' :
				// Link to the onetime account display
				$params = Array(
					$this->settings->needLoginFormLoginReturnParam => $this->pi_getPageLink($TSFE->id, '', $this->getCalParams()),
				);
				$value = $this->pi_getPageLink($this->settings->needLoginFormLoginPid, '', $params);
				$marker = $this->applyWrap('loginlink', $value);
				break;
			case 'SUBMITBUTTON':
				$label = '';
				$cmd = '';
				if ($this->confName == 'list.submit') {
					$value = $this->pi_getLL('list_submit_submitbutton');
				} else {
					switch ($this->registration->getStatus()) {
						case 3:
							$label = 'label_submit_register_normal';
							$cmd = 'register';
							$onClick = '';
							break;
						case 4:
							$label = 'label_submit_register_waitlist';
							$cmd = 'register';
							$onClick = '';
							break;
						case 5:
							if ($this->settings->disableUnregister == 0) {
								$label = 'label_submit_unregister_normal';
								$cmd = 'unregister';
								$onClick = ' onClick="return confirm(\'' . $this->pi_getLL('label_question_unregister_normal') . '\')"';
							}
							break;
						case 6:
							if ($this->settings->disableUnregister == 0) {
								$label = 'label_submit_unregister_waitlist';
								$cmd = 'unregister';
								$onClick = ' onClick="return confirm(\'' . $this->pi_getLL('label_question_unregister_waitlist') . '\')"';
							}
							break;
					}

					if ($label) {
						if ($this->registration->getUserField('uid') == $TSFE->fe_user->user['uid']) {
							$value = $this->getHiddenFields(1, $cmd);
						} else {
							$other = array(
								'subcmd' => $cmd,
								'uid' => $this->registration->getEventField('uid'),
								'getdate' => $this->registration->getEventDate(),
								'userid' => $this->registration->getUserField('uid'),
							);
							$value = $this->getHiddenFields(1, 'registerForeignUser', $other);
						}

						$value .= '<input type="submit" value="' . $this->pi_getLL($label) . '"' . $onClick . ' />';
					}
				}
				$marker = $this->applyWrap(strtolower($singleMarker), $value);

				break;
			case 'UNREGISTER':
				//THER020111: used only for old templates
				//if ($this->view == 'single') {
				$value = $this->getHiddenFields(1, 'unregister');
				$marker = $this->applyWrap('unregister', $value);
				//}
				break;
			case 'USERSELECTION':
				if ($this->registration->getUserField('uid') == 0) {
					$other = array(
						'subcmd' => 'selectuser',
						'uid' => $this->registration->getEventField('uid'),
						'getdate' => $this->registration->getEventDate(),
					);
					$value = '<form action="###LINK###" method="post">###LABEL_user_name###: <select size="1" name="' . $this->prefixId . '[userid]">' .
							'<option value="0" selected>' . $this->pi_getLL('label_select_user') . '</option>';
					foreach ($this->userList as $uid => $name)
						$value.= '<option value="' . $uid . '">' . $name . '</option>';
					$value .= $this->getHiddenFields(1, 'registerForeignUser', $other);
					$value .= '</select><input type="submit" value="###LABEL_admin_selectuser###"></form>';
				} else {
					$other = array(
						'subcmd' => 'selectuser',
						'uid' => $this->registration->getEventField('uid'),
						'getdate' => $this->registration->getEventDate(),
						'userid' => 0,
					);
					$value = '<form action="###LINK###" method="post">###LABEL_user_name###: ###USER_name### ';
					$value .= $this->getHiddenFields(1, 'registerForeignUser', $other);
					$value .= '</select><input type="submit" value="###LABEL_admin_changeuser###"></form>';
				}
				$value .= '<form action="###LINK###" method="post"><input type="submit" value="###LABEL_admin_back###"></form>';
				$marker = $this->applyWrap('userselection', $value);
				break;
			case 'FOREIGNUSERREGISTRATION':
				if ($this->registration->getUserField('uid') != 0) {
					$subView = self::getInstance();
					$subView->setRegistration($this->registration);
					switch ($this->registration->getStatus()) {
						case 0;  // No registration active
						// fall through
						case 1:  // No registration possible at the moment (outside registration period)
						// fall trough
						case 2:  // no registration possible at the moment (event fully booked)
							$content = '';
							break;
						case 3:  // Normal registration is possible
							$subView->load('single.registration.enter');
							$value = $subView->render();
							break;
						case 4:  // Waitlist enlisting is possible
							$subView->load('single.waitlist.enter');
							$value = $subView->render();
							break;
						case 5:  // User has already registered
							$subView->load('single.registration.alreadyDone');
							$value = $subView->render();
							break;
						case 6:  // User has already enlisted on waitlist
							$subView->load('single.waitlist.alreadyDone');
							$value = $subView->render();
							break;
					}
				} else $value = '';
				$marker = $this->applyWrap('foreignuserregistration', $value);
				break;
			default :
				$marker = parent::renderSingleMarker($singleMarker);
				break;
		}
		return $marker;
	}
        
       /**
	* Render subpart of template and return result
	* @param String $subpartName Name of subpart
	* @return <type> Subpart content
	*/
        public function renderSubpart($subpartName) {
            // Don't show UNREGISTER_ENABLED if unregistering is disabled
            if ($subpartName=='UNREGISTER_ENABLED' && $this->settings->disableUnregister) 
                return '';
            
            return parent::renderSubpart($subpartName);
        }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_register_view.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_register_view.php']);
}
?>
