<?php
/***************************************************************
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
***************************************************************/
/**
 * class.tx_register4cal_fehooks.php
 *
 * Provide rendering functions for extension register4cal 
 *
 * $Id$
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(t3lib_extMgm::extPath('cal') . 'res/pearLoader.php'); 
require_once(t3lib_extMgm::extPath('cal') . 'model/class.tx_cal_phpicalendar_model.php');
require_once(t3lib_extMgm::extPath('cal') . 'controller/class.tx_cal_registry.php');

/**
 * Main rendering functions for extension 'register4cal' 
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_render {
	private $pi_base;			//Instance of pi_base object
	private $cObj;				//Instance of cObj object
	private $settings = Array();		//Array containing TypoScript settings
	private $event;				//Array containing event data
	private $registration;			//Array containing registration record
	private $user;				//Array containing user record
	private $view;				//view mode ('single' or 'list')
	private $message;			//error message
	
	/*
         * Constructor for class tx_register4cal_render
         *
	 * @param	instance	$referring_pi_base: instance of class, implementing pi_base, which is using this class
	 * @param	array		$settings: Array containing the settings
	 *
         * @return	void
         */	
	public function tx_register4cal_render($referring_pi_base, &$settings) {
			// instance of pi_base referring to this class
		$this->pi_base = $referring_pi_base;
		$this->cObj = $referring_pi_base->cObj;
		$this->settings = &$settings;
		
			// clear variables
		unset($this->event);
		unset($this->registration);
		unset($this->user);
		unset($this->view);
	}

/***********************************************************************************************************************************************************************
 *
 * Getter and Setter Methods 
 *
 **********************************************************************************************************************************************************************/
	/*
	 * Sets the message
	 *
	 * @param	string	$message: Message
	 *
	 * @return	void
	 */
	public function setMessage($message) {
		$this->message = $message;
	}
	
	/*
	 * Sets the view (single event or event list)
	 *
	 * @param	string	$view: View ("single" or "list")
	 *
	 * @return	void
	 */
	public function setView($view) {
		switch($view) {
			case 'single':
				//Fall trough
			case 'list':
				$this->view = $view;
				break;
			default:
				die('Unknown view "' . $view . '" in tx_register4cal_render->setView. Notify developer!');
		}
	}
	
	/*
         * Sets the event
         *
	 * @param	array		$event: Array with the event data
	 *
         * @return 	nothing
         */	
	public function setEvent($event) {
		$this->unsetEvent();
		$this->event = Array();
		$this->event['data'] = $event;
		
			// instanciate event object
		$tx_cal_phpicalendar_model = &t3lib_div::makeInstanceClassName('tx_cal_phpicalendar_model');
		$this->event['object'] = new $tx_cal_phpicalendar_model($event, FALSE, 'tx_cal_phpicalendar');

			// prepare other event information
		$this->prepareFormatedDateTime();
		$this->getOrganizerData();
		$this->event['userfields'] = $this->getUserfieldData($event);
	}

	/*
         * Unsets the event
         *
         * @return 	void
         */
	public function unsetEvent() {
		unset($this->event);
	}

	/*
         * Sets the registration
         *
	 * @param	array		$registration: Array with the registration data
	 *
         * @return 	void
         */	
	public function setRegistration($registration) {
		$this->unsetRegistration();
		$this->registration = $registration;
		$this->prepareFormatedDateTime();
	}

	/*
         * Unsets the registration
         *
         * @return 	void
         */
	public function unsetRegistration() {
		unset($this->registration);
		unset($this->event['formatedStart']);
		unset($this->event['formatedEnd']);
	}
	
	/*
         * Sets the user
         *
	 * @param	array		$user: Array with the user data
	 *
         * @return 	void
         */	
	public function setUser($user) {
		$this->unsetUser();
		$this->user = $user;
	}

	/*
         * Unsets the user
         *
         * @return 	void
         */
	public function unsetUser() {
		unset($this->user);
	}
	
/***********************************************************************************************************************************************************************
 *
 * Main rendering functions 
 *
 **********************************************************************************************************************************************************************/	
       /*
         * Renders a form
	 *      
	 * @param	array		@confName: Name of TS config for the form to render
	 * @param	string		@mode: View-Mode to render (edit or show)
	 * @param	string		@templateSubpartSubpart: Subpart of the template subpart to use
	 * @param	array		@presetSubpartMarker: Array containing preset subpart markers to use
	 * @param	array		@presetMarker: Array containting preset markers to use
	 *
         * @return 	string		Rendered fields
         */	
	public function renderForm($confName, $mode, $templateSubpartSubpart = '', $presetSubpartMarker = Array(), $presetMarker = Array()) {
			// get requested configuration
		$conf = $this->settings['forms'];
		$confArray = explode('.', $confName);
		foreach ($confArray as $confPart) {
			$conf = $conf[$confPart.'.'];
		}
		
		if (!isset($conf)) return $this->renderError('Configuration "'.$confName.'" not set!');
		
			// get requested template subpart
		$templateSubpart = '###'.$conf['subtemplate'].'###';
		$template = $this->cObj->getSubpart($this->settings['template'], $templateSubpart);
		if ($template == '') return $this->renderError('Template "'.$templateSubpart.'" not set!');
		if ($templateSubpartSubpart != '') $template = $this->cObj->getSubpart($template, $templateSubpartSubpart);
		
		
		
			// replace preset subparts in the template
		foreach ($presetSubpartMarker as $marker => $content) {
			$template = $this->cObj->substituteSubpart($template, $marker, $content);
		}
			// replace remaining subparts
		$count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $template, $match);
		while ($count > 0) {
			$allSubparts = array_unique($match[1]);
			foreach ($allSubparts as $singleSubpart) {
				$subpartContent = $this->cObj->getSubpart($template, '###' . $singleSubpart . '###');
				$subpartContent = $this->applyWrap($subpartContent, $conf, strtolower($singleSubpart), $mode);
				$template = $this->cObj->substituteSubpart($template, '###' . $singleSubpart . '###', $subpartContent,0);
			}
			$count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $template, $match);
		}
		
			// replace markers in template
		$marker = $presetMarker;
		$count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $template, $match);
		while ($count > 0) {
			$allMarkers = array_unique($match[1]);
			foreach ($allMarkers as $singleMarker) {
				if (!isset($marker['###' . $singleMarker . '###'])) $marker['###' . $singleMarker . '###'] = $this->renderSingleMarker($singleMarker, $conf, $mode);
			}
			$template = $this->cObj->substituteMarkerArray($template, $marker);
			$count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $template, $match);
		}
		return $template;
	}	
	
	
	/*
         * Renders a subject for an email
	 *      
	 * @param	array		@confName: Name of TS config for the form to render
	 *
         * @return 	string		Rendered fields
         */	
	public function renderSubject($confName) {
			// get requested configuration
		$conf = $this->settings['forms'];
		$confArray = explode('.', $confName);
		foreach ($confArray as $confPart) {
			$conf = $conf[$confPart.'.'];
		}
		
		return $this->applyWrap('', $conf, 'subject', 'show');
	}
	
	public function renderError($error) {
		$content = '<div style="border:2px solid red;width:100%;padding:2px;margin_2px;background-color:red;">'.
			   '<span style="font-size:x-large;color:yellow;">Extension register4cal: Error</span>'.
			   '<p style="background-color:white;padding:1em;">'.$error.'</p>'.
			   //'<p style="background-color:white;padding:1em;">'.t3lib_div::debug_trail().'</p>'.
			   '</div>';
		return $content;
	}


/***********************************************************************************************************************************************************************
 *
 * Functions, supporting the rendering process 
 *
 **********************************************************************************************************************************************************************/	
        /*
         * Apply stdWrap to a value
         *
	 * $conf is usualy a branch of the "forms"-section in the TypoScript config, except the "allForms" section.
	 * This function now checks, whether a subsection for the field is contained in the given section. If not,
	 * the according subsection of the "default" section is being taken. If this subsection contains branches
	 * called "show" and/or "edit". The branch matching the given mode is being used as stdWrap-Configuration.
	 * Otherwise the whole subsection is being used.
	 * Important: fieldnames are converted into lowercase before!
	 *
	 * @param	string		$value: Value to wich stdWrap should be applied	
	 * @param	array		$conf: Array containing the configuration of the fields from TypoScript
	 * @param	string		$field: Fieldname to determine stdWrap-Configuration
	 * @param	string		$mode: Mode to render (currently "edit" and "show" are supported)
	 *
         * @return 	string		Value with stdWrap applied
         */	
	private function applyWrap($value, $conf, $field, $mode) {
		$field = strtolower($field);
		$config = $conf[$field . '.'];
		if (!isset($config)) $config = $this->settings['default'][$field . '.'];
		$config = isset($config['show.']) || isset($config['edit.']) ? $config[$mode . '.'] : $config;
		return $this->cObj->stdWrap($value, $config);
	}	
	
        /*
         * Render a single userfield
         *
	 * @param	array		$field: Array containing the configuration of the field from database
	 * @param	array		$conf: Array containing the configuration of the fields from TypoScript
	 * @param	string		$mode: Mode to render (currently "edit" and "show" are supported)
	 * @param	string		$value: Value of the field. Required for mode="show"
	 
         * @return 	string		Rendered field
         */
	private function renderUserField($field,  $conf, $mode,  $value='') { //$conf,
			// fieldname
		$fieldname = 'FIELD_' . $field['name'];
		if ($this->view == 'single') {
			$fieldnamePrefix = htmlspecialchars($this->pi_base->prefixId . '[' . $fieldname . ']');
		} elseif ($this->view == 'list') {
			$fieldnamePrefix = htmlspecialchars($this->pi_base->prefixId . '[' . $this->event['data']['uid'] . '][' . $this->event['data']['start_date'] . '][' . $fieldname . ']');
		}

			// value of field
		if ($mode == 'edit') $value = isset($this->pi_base->piVars[$fieldname]) ? $this->pi_base->piVars[$fieldname] : $field['defaultvalue'];
		$value = htmlspecialchars($value);
		
		if ($mode == 'edit') {
			switch ($field['type']) {
				case 1: 	// Single textfield
					$size = $field['width'] != 0 ? $field['width'] : 30;
					$content = '<input type="text" size="' . $size . '" name="' . $fieldnamePrefix . '" value="' . $value . '" />';
					break;
				
				case 2:		// Multiline textfield
					$cols = $field['width'] != 0 ? $field['width'] : 30;
					$rows = $field['height'] != 0 ? $field['height'] : 5;
					$content = '<textarea rows="' . $rows . '" cols="' . $cols . '" name="' . $fieldnamePrefix . '">' . $value . '</textarea>';
					break;
				
				case 3:		// Select field
					$size = $field['height'] != 0 ? $field['height'] : 1;
					$options = '';
					$optArray = explode('|',$field['options']);
					foreach($optArray as $optValue) {
						$selected = $optValue == $field['defaultvalue'] ? ' selected' : '';
						$options .= '<option' . $selected . '>' . htmlspecialchars($optValue) . '</option>';
					}
					$content = '<select size="' . $size . '" name="' . $fieldnamePrefix . '">' . $options . '</select>';
					break;
			
			}
		} else {
			switch ($field['type']) {
				case 1: 	// Single textfield
					$content = $value;
					break;
				
				case 2:		// Multiline textfield
					$cols = $field['width'] != 0 ? $field['width'] : 30;
					$rows = $field['height'] != 0 ? $field['height'] : 5;
					$content = '<textarea rows="' . $rows . '" cols="' . $cols . '" name="' . $fieldnamePrefix . '" readonly="yes">' . $value . '</textarea>';
					break;
				
				case 3:		// Select field
					$content = $value;
					break;
			
			}		
		}

			// render the field
		$content = $this->applyWrap($content, $conf, 'userfield', $mode);
		$marker = Array();
		$marker['###CAPTION###'] = htmlspecialchars($field['caption']);
		$content = $this->cObj->substituteMarkerArray($content, $marker);	
		return $content;
	}	
	
	/*
         * Render all userdefined fields (entry or display mode)
         *
	 * @param	array		$conf: Array containing the configuration of the fields from TypoScript
	 * @param	string		$mode: Mode to render (currently "edit" and "show" are supported)
	 
         * @return 	string		Rendered fields
         */	
	private function renderUserFields($conf, $mode) {
		$field = '';
		if ($mode == 'show' && isset($this->registration['additional_data'])) {
				// Mode = Show: Render based on userfields in registration record
			$fieldsarray = unserialize($this->registration['additional_data']);
			if (is_array($fieldsarray)) {
				foreach ($fieldsarray as $name => $field) {
					$fields .= $this->renderUserField($field['conf'], $conf, $mode, $field['value']);
				}
			}
		} elseif ($mode == 'edit' && isset($this->event['userfields'])){
				// Mode = Edit: Render fields based on userfields in database
			if (is_array($this->event['userfields'])) {
				foreach ($this->event['userfields'] as $field) {
					$fields .= $this->renderUserField($field, $conf, $mode);
				}
			}
		}

		if ($mode == 'edit') {	
			$hiddenFields = '';
			if ($this->view == 'single') {
				$calPiVars = t3lib_div::GParrayMerged('tx_cal_controller');
				foreach ($calPiVars as $name => $value) {
					$hiddenFields .= '<input type="hidden" name="tx_cal_controller[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '" />';
				}
				$hiddenFields .= '<input type="hidden" name="' . $this->pi_base->prefixId . '[cmd]" value="register" />';
				$hiddenFields .= '<input type="hidden" name="no_cache" value="1" />';
				$fields .= $this->applyWrap($hiddenFields, $conf, 'submitbutton', $mode);
			}		
		}		
		return $fields;
	}
	
	/*
         * Render a single marker
         *
	 * @param	string		$singleMarker: marker string, without ###
	 * @param	array		$conf: Relevant configuration array, containing the stdWrap settings for the elements
	 * @param	string		$mode: Mode to render 
	 *
         * @return 	string		content to replace the marker
         */
	private function renderSingleMarker($singleMarker, $conf, $mode) {
		switch ($singleMarker) {
			case 'ERRORMESSAGE' :
					// Render the error message
				$value = $this->message;
				$marker = $this->applyWrap($value, $conf, 'errormessage', $mode);
				break;	
			case 'FIELDS' :
					// Render the userfields
				$fields = $this->renderUserFields($conf, $mode);
				$marker = $this->applyWrap($fields, $conf, 'fields', $mode);
				break;
			case 'LINK' :
					// Marker for the registration form
				$marker = $this->applyWrap(htmlspecialchars($this->pi_base->pi_linkTP_keepPIvars_url()), $conf, 'link', $mode);
				break;
			case 'ONETIMEACCOUNTLINK' :
					// Link to the onetime account display
				$sourceParams = Array();
				$calPiVars = t3lib_div::GParrayMerged('tx_cal_controller');
				foreach ($calPiVars as $name => $value) {
					$sourceParams['tx_cal_controller[' . htmlspecialchars($name) . ']'] = htmlspecialchars($value);
				}
				$params = Array();
				$params[$this->settings['onetimereturnparam']] = $this->pi_base->pi_getPageLink($GLOBALS["TSFE"]->id, '', $sourceParams);
				$value = $this->pi_base->pi_getPageLink($this->settings['onetimepid'], '', $params);
				$marker = $this->applyWrap($value, $conf, 'onetimeaccountlink', $mode);
				break;
			case 'LOGINLINK' :
					// Link to the onetime account display
				$sourceParams = Array();
				$calPiVars = t3lib_div::GParrayMerged('tx_cal_controller');
				foreach ($calPiVars as $name => $value) {
					$sourceParams['tx_cal_controller[' . htmlspecialchars($name) . ']'] = htmlspecialchars($value);
				}
				$params = Array();
				$params[$this->settings['loginreturnparam']] = $this->pi_base->pi_getPageLink($GLOBALS["TSFE"]->id, '', $sourceParams);
				$value = $this->pi_base->pi_getPageLink($this->settings['loginpid'], '', $params);
				$marker = $this->applyWrap($value, $conf, 'loginlink', $mode);
				break;
			case 'STATUS' :
				$value = $this->pi_base->pi_getLL('label.status.'.intval($this->registration['status']));
				$marker = $this->applyWrap($value, $conf, 'status', $mode);
				break;
			case 'UNREGISTER':
				$hiddenFields = '';
				if ($this->view == 'single') {
					$calPiVars = t3lib_div::GParrayMerged('tx_cal_controller');
					foreach ($calPiVars as $name => $value) {
						$hiddenFields .= '<input type="hidden" name="tx_cal_controller[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '" />';
					}
					$hiddenFields .= '<input type="hidden" name="' . $this->pi_base->prefixId . '[cmd]" value="unregister" />';
					$hiddenFields .= '<input type="hidden" name="no_cache" value="1" />';
					$marker = $this->applyWrap($hiddenFields, $conf, 'unregister', $mode);
				}	
				break;
			case 'MAXATTENDEES':
				$value = $this->event['data']['tx_register4cal_maxattendees'];
				$marker = $this->applyWrap($value, $conf, 'maxattendees', $mode);
				break;
			case 'NUMATTENDEES':
				if (!isset($this->event['regcount'])) $this->event['regcount'] = tx_register4cal_user1::getRegistrationCount($this->event['data']['uid'], $this->event['data']['start_date'], $this->event['data']['pid']);
				$value = $this->event['regcount'][1];
				$marker = $this->applyWrap($value, $conf, 'numattendees', $mode);
				break;
			case 'NUMFREE':
				if (!isset($this->event['regcount'])) $this->event['regcount'] = tx_register4cal_user1::getRegistrationCount($this->event['data']['uid'], $this->event['data']['start_date'], $this->event['data']['pid']);
				$value = $this->event['data']['tx_register4cal_maxattendees'] - $this->event['regcount'][1];
				$marker = $this->applyWrap($value, $conf, 'numfree', $mode);
				break;
			case 'NUMWAITLIST':
				if (!isset($this->event['regcount'])) $this->event['regcount'] = tx_register4cal_user1::getRegistrationCount($this->event['data']['uid'], $this->event['data']['start_date'], $this->event['data']['pid']);
				$value = $this->event['regcount'][2];
				$marker = $this->applyWrap($value, $conf, 'numwaitlist', $mode);
				break;
			case 'WAITLISTCHECKBUTTON':
				$value = 	'<form action="'. $this->pi_base->pi_getPageLink($GLOBALS["TSFE"]->id) . '" method="post">'.
						'<input type="hidden" name="tx_register4cal_main[cmd]" value="checkwaitlist" />'.
						'<input type="hidden" name="tx_register4cal_main[uid]" value="' . $this->event['data']['uid'] . '" />'.
						'<input type="hidden" name="tx_register4cal_main[getdate]" value="' . $this->event['data']['start_date'] . '" />'.
						'<input type="submit" value = "' . $this->pi_base->pi_getLL('label.checkwaitlistbutton') . '" />'.
						'</form>';
				$marker = $this->applyWrap($value, $conf, 'waitlistcheckbutton', $mode);
				break;
			default :
				if (preg_match('/EVENT_([A-Z0-9_-])*/', $singleMarker)) {
						// Insert an event field. We have some special replacements here ...
					$fieldname = substr($singleMarker, 6);
					switch ($fieldname) {
						case 'link' :
							$value = $this->getEventLink();
							break;
						case 'organizer_name' :
							$value = $this->event['organizerName'];
							break;
						case 'organizer_email' :
							$value = $this->event['organizerEmail'];
							break;
						case 'formated_start' :
							if (!isset($this->event['formatedStart'])) $this->prepareFormatedDateTime();
							$value = $this->event['formatedStart'];
							break;
						case 'formated_end' :
							if (!isset($this->event['formatedStart'])) $this->prepareFormatedDateTime();
							$value = $this->event['formatedEnd'];
							break;
						case 'get_date' :
							$value = $this->event['data']['start_date'];
							break;
						case 'start_date' :
							$value = $this->event['start']->format($this->settings['date_format']);
							break;
						case 'end_date' :
							$value = $this->event['end']->format($this->settings['date_format']);
							break;
						case 'start_time' :
							$value = $this->event['object']->getStart()->format($this->settings['time_format']);
							break;
						case 'end_time' :
							$value = $this->event['object']->getEnd()->format($this->settings['time_format']);
							break;
						case 'teaser' :
							// Fall trough
						case 'description' :
							$value = isset($this->event['data'][$fieldname]) ? $value = $this->event['data'][$fieldname] : '';
							$value = $this->pi_base->pi_RTEcssText($value);
						default :
							$value = isset($this->event['data'][$fieldname]) ? $value = $this->event['data'][$fieldname] : '';
					}
				} elseif (preg_match('/LOCATION_([A-Z0-9_-])*/', $singleMarker)) {
						// Insert a field from the location record
					$fieldname = substr($singleMarker, 9);
					if (!isset($this->event['location'])) $this->setLocationObj();
					$value = isset($this->event['location'][$fieldname]) ? $value =$this->event['location'][$fieldname] : '';
				} elseif (preg_match('/ORGANIZER_([A-Z0-9_-])*/', $singleMarker)) {
						// Insert a field from the organizer record
					$fieldname = substr($singleMarker, 10);
					if (!isset($this->event['organizer'])) $this->setOrganizerObj();
					$value = isset($this->event['organizer'][$fieldname]) ? $value =$this->event['organizer'][$fieldname] : '';
				} elseif (preg_match('/USER_([A-Z0-9_-])*/', $singleMarker)) {
						// Insert an user field
					$fieldname = substr($singleMarker, 5);
					$value = isset($this->user[$fieldname]) ? $value =$this->user[$fieldname] : '';
				} elseif (preg_match('/LABEL_([A-Z0-9_-])*/', $singleMarker)) {
						// Insert a label field
					$fieldname = 'label.' . str_replace('-', '.', substr($singleMarker, 6));;
					$value = $this->pi_base->pi_getLL($fieldname);
				} else {
					$value='';
				}
				$marker =  $this->applyWrap($value, $conf, $singleMarker,$mode);
				break;
		}
		return $marker;
	}
	
	/*
         * Get the link to the event single view
	 *
	 * Hint:
	 *  If event- and registration information have been retrieved in one select, they are contained in a single associative array.
	 *  In this case, this array can be assigned to both $eventRow and $registrationRow
         *
	 * @param	array		$eventRow: associative array containing the event record
	 * @param	array		$registrationRow: associative array containing the registration record for the event
	 *
         * @return 	string		Link target for event single view
         */	
	function getEventLink() {
		$vars = array();
		$vars['tx_cal_controller[view]'] = 'event';
		$vars['tx_cal_controller[type]'] = 'tx_cal_phpicalendar';

		if (empty($this->registration)) {
			$vars['tx_cal_controller[getdate]'] = intval($this->event['data']['start_date']);
			$vars['tx_cal_controller[uid]'] = intval($this->event['data']['uid']);
		} else {
			$vars['tx_cal_controller[getdate]'] = intval($this->registration['cal_event_getdate']);
			$vars['tx_cal_controller[uid]'] = intval($this->registration['cal_event_uid']);
		}
		return $this->pi_base->pi_getPageLink($this->settings['eventpid'], '', $vars);
	}	
	
	/*
         * Prepare a formated startdate/-time and enddate/-time
	 * This also calculates the actual start- and enddate for
	 * recurring events.
	 *
         * @return 	void
         */	
	private function prepareFormatedDateTime() {
		if (isset($this->event['data'])  && !isset($this->event['formatedStart'])) {
				// format date and time
			$this->event['start'] = new tx_cal_date();
			$this->event['start']->copy($this->event['object']->getStart());
			$this->event['end'] = new tx_cal_date();
			$this->event['end']->copy($this->event['object']->getEnd());
			if ($this->event['data']['freq'] != 'none' && !empty($this->registration)) {;
				$delta = (strtotime($this->registration['cal_event_getdate']) - strtotime($this->event['data']['start_date']));
				$this->event['start']->addSeconds($delta);
				$this->event['end']->addSeconds($delta);
			}
			$format = $this->event['data']['allday'] == 0 ? $this->settings['date_format'].' '.$this->settings['time_format'] : $this->settings['date_format'];
			$this->event['formatedStart'] = $this->event['start']->format($format);
			$this->event['formatedEnd'] = $this->event['end']->format($format);			
			if ($this->event['allday'] != 0) {
				$this->event['formatedStart'] .= ' ' . $this->pi_base->pi_getLL('event_allday');
				$this->event['formatedEnd'] = '';
			}
		}
	}

	/*
         * Read organizer record for current event
	 *
         * @return 	void
         */
	function setOrganizerObj() {
		if (isset($this->event['data']['organizer_id'])) {
			$select = '*';
			$table = 'tx_cal_organizer';
			$where = 'uid=' . intval($this->event['data']['organizer_id']) . $this->cObj->enableFields('tx_cal_organizer');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$this->event['organizer'] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
	}
	
	/*
         * Read location record for current event
	 *
         * @return 	void
         */
	function setLocationObj() {
		if (isset($this->event['data']['location_id'])) {
			$select = '*';
			$table = 'tx_cal_location';
			$where = 'uid=' . intval($this->event['data']['location_id']) . $this->cObj->enableFields('tx_cal_location');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$this->event['location'] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
	}

	/*
         * Provide the name of the organizer for an event
	 *
         * @return 	void
         */	
	function getOrganizerData() {
		if ($this->event['data']['organizer_id']==0) {
				// Organizer in event record, email not available
			$this->event['organizerName'] = $this->event['data']['organizer'];
			$this->event['organizerEmail'] ='';
		} else {
				// Organizer and email in separate organizer record
			$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cal']);
			$useOrganizerStructure = ($confArr['useOrganizerStructure'] ? $confArr['useOrganizerStructure'] : 'tx_cal_organizer');

				// which table to use?
			switch ($useOrganizerStructure) {
				case 'tx_tt_address':
					$table = 'tt_address';
					break;
				case 'tx_partner_main':
					$table = 'tx_partner_main';
					break;
				case 'tx_feuser':
					$table = 'fe_users';
					break;
				default:
					$table = 'tx_cal_organizer';
					break;
			}
				
				// read data
			$select = '*';
			$where = 'uid=' . intval($this->event['data']['organizer_id']);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$this->event['organizerName'] = $row['name'];
			$this->event['organizerEmail'] = $row['email'];
			$this->event['organizer'] = $row;
			$this->settings['organizer_email'] = $row['email'];
		}
	}
	
	public function getUserfieldData($event) {
		$ufData = Array();
			// determine fields only if registration is active 
		if ($event['tx_register4cal_activate'] == 1 && $event['tx_register4cal_fieldset'] != -2) { 
				// if fieldset is set, read it, otherwise read first fieldset having the "isdefault" flag set
			if ($event['tx_register4cal_fieldset'] != 0 && $event['tx_register4cal_fieldset'] != -1) {
				$where = 'uid=' . intval($event['tx_register4cal_fieldset']) . ' AND pid=' . intval($event['pid']);
			} else {
				$where = 'isdefault <> 0' . ' AND pid=' . intval($event['pid']);
			}
			
				//read fieldset
			$resFS = $GLOBALS['TYPO3_DB']->exec_SELECTquery('fields','tx_register4cal_fieldsets', $where);
			if($rowFS = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resFS)) {
					//read fields
				$fieldlist = $GLOBALS['TYPO3_DB']->cleanIntList($rowFS['fields']);
				$resFD = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_register4cal_fields','uid IN (' . $fieldlist . ') AND sys_language_uid IN (0,-1)');
				while ($rowFD = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resFD)) {
						// translate
					$rowFD = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_register4cal_fields', $rowFD, $GLOBALS['TSFE']->sys_language_uid,$GLOBALS['TSFE']->config['config']['sys_language_overlay']);
					$ufData[$rowFD['name']] = $rowFD;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($resFS);
		}
		return $ufData;
	}
	
	public function getNumberOfAttendeesField() {
			// determine userfield containing number of attendees
		foreach($this->event['userfields'] as $name => $data) {
			if ($data['isnumparticipants']) {
				$field = $name;
				break;
			}
		}
		return $field;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_render.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_render.php']);
}
        
?>