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
 * class.tx_register4cal_userdefinedfield_view.php
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

/**
 * View for a userdefined field, defining it's renering
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_userdefinedfield_view {
	//TODO SEV9 Version 0.7.1: Add hooks to this class, enabling the definition of additional field types
	
	/* =========================================================================
	 * Private variables
	 * ========================================================================= */

	/**
	 * prefixId for field names (just like tslib_pibase)
	 * @var string
	 */
	private $prefixId = 'tx_register4cal_view';
	/**
	 * Instance of tx_register4cal_registration model, containing all data
	 * @var tx_register4cal_registration
	 */
	private $registration;
	/**
	 * Field configuration, taken from $this->registration->getUserdefinedField($name)['conf']
	 * @var Array
	 */
	private $conf = Array();
	/**
	 * Value of field. taken from $this->registration->getUserdefinedField($name)['value']
	 * if this is FALSE, $this->registration->getUserdefinedField($name)['conf']['defaultvalue']
	 * is used
	 * @var mixed
	 */
	private $value;
	/**
	 * name of field
	 * @var string
	 */
	private $name = '';
	/**
	 * Flag: Render in display mode only (no Input mode)
	 * @var boolean
	 */
	private $renderDisplayOnly = FALSE;

	/* =========================================================================
	 * Constructor and static getInstance() method
	 * ========================================================================= */

	/**
	 * Create an instance of the class while taking care of the different ways
	 * to instanciace classes having constructors with parameters in different
	 * Typo3 versions
	 * @param string $name @see __construct
	 * @param tx_register4cal_registration $registration @see __construct
	 * @param boolean $renderDisplayOnly @see __construct
	 * @return tx_register4cal_userdefinedfield_view
	 */
	public static function getInstance($name, $registration, $renderDisplayOnly) {
		$className = 'tx_register4cal_userdefinedfield_view';
		if (t3lib_div::int_from_ver(TYPO3_version) <= 4003000) {
			$className = &t3lib_div::makeInstanceClassName($className);
			$class = new $className($name, $registration, $renderDisplayOnly);
		} else {
			$class = &t3lib_div::makeInstance($className, $name, $registration, $renderDisplayOnly);
		}
		return $class;
	}

	/**
	 * Class constructor
	 * @param string $name Name of field for which the class should be instanciated
	 * @param tx_register4cal_registration $registration registration data to use
	 * @param boolean $renderDisplayOnly Flag: Render in display mode only
	 */
	public function __construct($name, $registration, $renderDisplayOnly) {
		$this->name = $name;
		$this->registration = $registration;
		$this->renderDisplayOnly = $renderDisplayOnly;
		$field = $this->registration->getUserdefinedField($name);
		if ($field === FALSE) throw new Exception('Field "' . $name . '" is unknown!');
		$this->conf = $field['conf'];
		$this->value = ($field['value'] === FALSE) ? $field['conf']['defaultvalue'] : $field['value'];
	}

	/* =========================================================================
	 * Public methods
	 * ========================================================================= */
	/**
	 * Returns the caption for the field (localized)
	 * @return string
	 */
	public function getCaption() {
		return $this->conf['caption'];
	}

	/**
	 * Return the rendered field
	 * @return string HTML for rendered field
	 */
	public function render() {
		if ($this->renderDisplayOnly) {
			return $this->renderDisplay();
		} else {
			switch ($this->registration->getStatus()) {
				case 3: // fall through
				case 4:
					return $this->renderInput();
					break;
				default:
					return $this->renderDisplay();
					break;
			}
		}
	}

	/* =========================================================================
	 * Private methods
	 * ========================================================================= */
	/**
	 * Returns the field rendered for display purposes
	 * @return string
	 */
	private function renderDisplay() {
		switch ($this->conf['type']) {
			case 1:  // Single textfield
				$content = $this->value;
				break;

			case 2:  // Multiline textfield
				$cols = $this->conf['width'] != 0 ? $this->conf['width'] : 30;
				$rows = $this->conf['height'] != 0 ? $this->conf['height'] : 5;
				$content = '<textarea rows="' . $rows . '" cols="' . $cols . '" readonly="yes">' . $this->value . '</textarea>';
				break;

			case 3:  // Select field
				$content = $this->value;
				break;
		}
		return $content;
	}

	/**
	 * Returns the field rendered for input purposes
	 * @return string
	 */
	private function renderInput() {
		$fieldname = htmlspecialchars($this->prefixId . '[' . $this->registration->getEventField('uid') . '][' . $this->registration->getEventDate() . '][' . 'FIELD_' . $this->name . ']');
		switch ($this->conf['type']) {
			case 1:  // Single textfield
				$size = $this->conf['width'] != 0 ? $this->conf['width'] : 30;
				$content = '<input type="text" size="' . $size . '" name="' . $fieldname . '" value="' . $this->value . '" />';
				break;

			case 2:  // Multiline textfield
				$cols = $this->conf['width'] != 0 ? $this->conf['width'] : 30;
				$rows = $this->conf['height'] != 0 ? $this->conf['height'] : 5;
				$content = '<textarea rows="' . $rows . '" cols="' . $cols . '" name="' . $fieldname . '">' . $this->value . '</textarea>';
				break;

			case 3:  // Select field
				$size = $this->conf['height'] != 0 ? $this->conf['height'] : 1;
				$options = '';
				$optArray = explode('|', $this->conf['options']);
				foreach ($optArray as $optValue) {
					$selected = $optValue == $this->value ? ' selected' : '';
					$options .= '<option' . $selected . '>' . htmlspecialchars($optValue) . '</option>';
				}
				$content = '<select size="' . $size . '" name="' . $fieldname . '">' . $options . '</select>';
				break;
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_userdefinedfield_view.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_userdefinedfield_view.php']);
}
?>
