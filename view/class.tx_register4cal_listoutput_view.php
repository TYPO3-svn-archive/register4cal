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
 * class.tx_register4cal_listoutput_view.php
 *
 * View class for list output
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_base_view.php');

/**
 * View class for list output
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_listoutput_view extends tx_register4cal_base_view {
	/* =========================================================================
	 * Private variables
	 * ========================================================================= */

	private $adminPanelEntries = Array();
	private $adminForeignUserData = Array();

	/* =========================================================================
	 * Constructor and static getInstance() methid
	 * ========================================================================= */
	/**
	 * Create an instance of the class while taking care of the different ways
	 * to instanciace classes having constructors with parameters in different
	 * Typo3 versions
	 * @return tx_register4cal_listoutput_view
	 */
	public static function getInstance() {
		$className = 'tx_register4cal_listoutput_view';
		if (tx_register4cal_static::getTypo3IntVersion() <= 4003000) {
			$className = &t3lib_div::makeInstanceClassName($className);
			$class = new $className();
		} else {
			$class = &t3lib_div::makeInstance($className);
		}
		return $class;
	}

	// Class constructor taken from parent class

	/* =========================================================================
	 * Properties (get and set)
	 * ========================================================================= */
	public function setAdminPanelEntries($adminPanelEntries) {
		$this->adminPanelEntries = $adminPanelEntries;
	}

	public function setAdminForeignUsersData($adminForeignUserData) {
		$this->adminForeignUserData = $adminForeignUserData;
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
		// TODO SEV9 Version 0.7.1 Define css classes/ids to enable formatting of html controls via css
		global $TSFE;
		switch ($singleMarker) {
			case 'FIELDS' :
				// Render the userfields				
				require_once(t3lib_extMgm::extPath('register4cal') . 'view/class.tx_register4cal_userdefinedfield_view.php');
				$fieldscontent = '';
				$fields = array_keys($this->registration->getUserdefinedFields());
				foreach ($fields as $fieldname) {
					$fieldclass = tx_register4cal_userdefinedfield_view::getInstance($fieldname, $this->registration, TRUE);
					$fieldcontent = $this->applyWrap('userfield', $fieldclass->render());
					$captionMarker = Array('###CAPTION###' => htmlspecialchars($fieldclass->getCaption()));
					$fieldscontent .= $this->cObj->substituteMarkerArray($fieldcontent, $captionMarker);
				}
				$marker = $this->applyWrap('fields', $fieldscontent);
				break;
			case 'ADMINPANEL':
				if (!$this->registration->userIsOrganizer() || count($this->adminPanelEntries) == 0) return '';
				$eventId = $this->registration->getEventField('uid');
				$eventDate = $this->registration->getEventDate();
				$value = '<form action="" method="post" class="tx_register4cal_adminpanel">' .
						'<input type="hidden" name="' . $this->prefixId . '[uid]" value="' . $eventId . '" />' .
						'<input type="hidden" name="' . $this->prefixId . '[getdate]" value="' . $eventDate . '" />' .
						'<select size="1" name="' . $this->prefixId . '[cmd]">' .
						'<option value="" selected>' . $this->pi_getLL('label_admin_none') . '</option>';
				foreach ($this->adminPanelEntries as $cmd => $label) {
					$value.= '<option value="' . $cmd . '">' . $this->pi_getLL($label) . '</option>';
				}
				$value.= '<input type="submit" value="' . $this->pi_getLL('label_admin_execute') . '" /></select></form>';

				$marker = $this->applyWrap('adminpanel', $value);
				break;

			// Start of compatibility code =====================================
			case 'WAITLISTCHECKBUTTON':
				if ($this->registration->userIsOrganizer() && isset($this->adminPanelEntries['checkwaitlist'])) {
					$eventId = $this->registration->getEventField('uid');
					$eventDate = $this->registration->getEventDate();
					$value = '<form action="" method="post" style="margin:0;padding:0;">' .
							'<input type="hidden" name="' . $this->prefixId . '[cmd]" value="checkwaitlist" />' .
							'<input type="hidden" name="' . $this->prefixId . '[uid]" value="' . $eventId . '" />' .
							'<input type="hidden" name="' . $this->prefixId . '[getdate]" value="' . $eventDate . '" />' .
							'<input type="submit" value = "' . $this->pi_getLL('label_checkwaitlistbutton') . '" />' .
							'</form>';
				}
				$marker = $this->applyWrap('waitlistcheckbutton', $value);
				break;
			// End of compatiblilty code =======================================
                        case 'PARTICIPANTVCARDLINK':
                            if (!$this->registration->IsParticipantVcardAllowed()) {
                                $marker = '';
                            } else {
                                $label = $this->pi_getLL('label_vcarddownload');
                                return $this->registration->getVcardLink($label,'P');
                            }
                        case 'ORGANIZERVCARDLINK':
                            if (!$this->registration->IsOrganizerVcardAllowed()) {
                                $marker = '';
                            } else {
                                $label = $this->pi_getLL('label_vcarddownload');
                                return $this->registration->getVcardLink($label,'O');
                            }
			default :
				$marker = parent::renderSingleMarker($singleMarker);
				break;
		}
		return $marker;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_listoutput_view.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_listoutput_view.php']);
}
?>
