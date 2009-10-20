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
require_once (t3lib_extMgm::extPath('cal') . 'model/class.tx_cal_phpicalendar_model.php');

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
	
	/*
         * Constructor for class tx_register4cal_render
         *
	 * @param	instance	$referring_pi_base: instance of class, implementing pi_base, which is using this class
	 * @param	array		$settings: Array containing the settings
	 *
         * @return	void
         */	
	public function tx_register4cal_render($referring_pi_base, $settings) {
			// instance of pi_base referring to this class
		$this->pi_base = $referring_pi_base;
		$this->cObj = $referring_pi_base->cObj;
		$this->settings = $settings;
		
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
		$this->event['object'] = &new $tx_cal_phpicalendar_model($event, FALSE, 'tx_cal_phpicalendar');

			// prepare other event information
		$this->prepareFormatedDateTime();
		$this->getOrganizerData();
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
	 * @param	string		@templateSupart: Template subpart to use
	 * @param	array		@confName: Name of TS config for the form to render
	 * @param	string		@mode: View-Mode to render (edit or show)
	 * @param	string		@templateSubpartSubpart: Subpart of the template subpart to use
	 * @param	array		@presetSubpartMarker: Array containing preset subpart markers to use
	 * @param	array		@presetMarker: Array containting preset markers to use
	 *
         * @return 	string		Rendered fields
         */	
	public function renderForm($templateSubpart, $confName, $mode, $templateSubpartSubpart = '', $presetSubpartMarker = Array(), $presetMarker = Array()) {
			// get requested template subpart
		$template = $this->cObj->getSubpart($this->settings['template'], $templateSubpart);
		if ($templateSubpartSubpart != '') $template = $this->cObj->getSubpart($template, $templateSubpartSubpart);
		
			// get requested configuration
		$conf = $this->settings['forms'][$confName . '.'];
		
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
		return $this->applyWrap('', $this->settings[$confName], 'subject', 'show');
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
	 * @param	array		$conf: Array containing the configuration of the field from TypoScript
	 * @param	string		$mode: Mode to render (currently "edit" and "show" are supported)
	 * @param	string		$value: Value of the field. Required for mode="show"
	 
         * @return 	string		Rendered field
         */
	private function renderUserField($conf, $mode,  $value='') {
		if ($mode == 'edit') $value = isset($this->pi_base->piVars[$fieldname]) ? $this->pi_base->piVars[$fieldname] : $conf['default'];
		$value = htmlspecialchars($value);
				
		$caption = $conf['caption.'][$this->settings['language']] != '' ? $conf['caption.'][$this->settings['language']] : $conf['caption.']['default'];
		$field = $this->cObj->stdWrap($value, $conf['layout.'][$mode . '.']);
		$options = '';
		if (isset($conf['options.'])) {
			foreach($conf['options.'] as $optionArray) {
				if (!is_array($optionArray)) {
					$option = $optionArray;
				} else {
					$option = $optionArray[$this->settings['language']] != '' ? $optionArray[$this->settings['language']] : $optionArray['default'];
				}
				$selected = $option == $value ? ' selected' : '';
				$options .= '<option' . $selected . '>' . htmlspecialchars($option) . '</option>';
			}
		}
		
		if ($this->view == 'single') {
			$fieldname = htmlspecialchars($this->pi_base->prefixId . '[FIELD_' . $conf['name'] . ']');
		} elseif ($this->view == 'list') {
			$fieldname = htmlspecialchars($this->pi_base->prefixId . '[' . $this->event['data']['uid'] . '][' . $this->event['data']['start_date'] . '][FIELD_' . $conf['name'] . ']');
		}
		
		$marker = Array();
		$marker['###NAME###'] = $fieldname;
		$marker['###CAPTION###'] = htmlspecialchars($caption);
		$marker['###OPTIONS###'] = $options;
		$field = $this->cObj->substituteMarkerArray($field, $marker);	
		return $field;
	}	

	/*
         * Render a single userfield which has been stored in register4cal version < 0.3.0
	 * This function will propably be removed in the furure
         *
	 * @param	array		$conf: Array containing the configuration of the field from TypoScript
	 * @param	string		$mode: Mode to render (currently "edit" and "show" are supported)
	 * @param	string		$value: Value of the field. Required for mode="show"
	 
         * @return 	string		Rendered field
         */
	private function renderOldUserField($field) {
		$caption = $field['caption'][$this->settings['language']] != '' ? $field['caption'][$this->settings['language']] : $field['caption']['default'];
		$template = $this->cObj->getSubpart($this->settings['template'], '###SHOW_' . strtoupper($field['type']) . '###');
		$marker = array();
		$marker['###SIZE###'] = htmlspecialchars($field['size']);
		$marker['###NAME###'] = htmlspecialchars($field['name']);
		$marker['###VALUE###'] =  htmlspecialchars($field['value']);
		$marker['###CAPTION###'] = htmlspecialchars($caption);
		return $this->cObj->substituteMarkerArray($template,$marker);
	}	
	
	/*
         * Render all userdefined fields (entry or display mode)
         *
	 * @param	array		$conf: Array containing the configuration of the field from TypoScript
	 * @param	string		$mode: Mode to render (currently "edit" and "show" are supported)
	 
         * @return 	string		Rendered fields
         */	
	private function renderUserFields($conf, $mode) {
		$fields = '';
		if ($mode == 'show' && isset($this->registration['additional_data'])) {
				// Mode = Show: Render based on userfields in registration record
			$fieldsarray = unserialize($this->registration['additional_data']);
			if (is_array($fieldsarray)) {
				foreach ($fieldsarray as $name => $field) {
					if (isset($field['type'])) {
						$fields .= $this->renderOldUserField($field);
					} else {
						// "new" version
						$fields .= $this->renderUserField($field['conf'], $mode, $field['value']);
					}				
				}
			}
		} elseif ($mode == 'edit' && isset($this->settings['userfields'])){
				// Mode = Edit: Render fields based on userfields in TypoScript
			if (is_array($this->settings['userfields'])) {
				foreach ($this->settings['userfields'] as $field) {
					$fields .= $this->renderUserField($field, $mode);
				}
			}
			
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
					$fieldname = 'label.' . str_replace('-', '.', substr($singleMarker, 6));
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
			$select = '*';
			$table = 'tx_cal_organizer';
			$where = 'uid=' . intval($this->event['data']['organizer_id']);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$this->event['organizerName'] = $row['name'];
			$this->event['organizerEmail'] = $row['email'];
			$this->event['organizer'] = $row;
			$this->settings['organizer_email'] = $row['email'];
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_render.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_register4cal_render.php']);
}
        
?>