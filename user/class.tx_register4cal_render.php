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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */ 

/**
 * Main rendering functions for extension 'register4cal' 
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 *
 * Modifications
 * ThEr020509	0.3.0	Initial development of class (complete revision of extension. Substantial changes in templates, TypoScript, etc.)
 */
 
require_once(t3lib_extMgm::extPath('cal').'res/pearLoader.php'); 
require_once (t3lib_extMgm::extPath('cal').'model/class.tx_cal_phpicalendar_model.php');

class tx_register4cal_render {
	public $settings = Array();
	private $userfields = Array();		
	private $event;
	private $event_obj;
	private $location_obj;
	private $organizer_obj;
	private $event_fStart;
	private $event_fEnd;
	private $event_orgName;
	private $event_orgEmail;
	private $event_rStart;
	private $event_rEnd;
	private $registration;
	private $pi_base;
	private $cObj;
	private $user;
	
	/*
         * Constructor for class tx_register4cal_render
         *
	 * @param	instance	$referring_pi_base: instance of class, implementing pi_base, which is using this class
	 *
         * @return	nothing
         */	
	public function tx_register4cal_render($referring_pi_base) {
		//instance of pi_base referring to this class
		$this->pi_base = $referring_pi_base;
		$this->cObj = $referring_pi_base->cObj;
		
		//init settings
		$tsconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.'];
		$this->settings['template_file'] = $tsconf['template'];
		$this->settings['date_format'] = $tsconf['dateformat'];
		$this->settings['time_format'] = $tsconf['timeformat'];
		$this->settings['eventpid'] = $tsconf['view.']['eventViewPid'];
		$this->settings['adminusers'] = explode(',',$tsconf['view.']['adminUsers']);
		$this->settings['language'] = $GLOBALS['TSFE']->tmpl->setup['config.']['language'];
		$this->settings['mailconf'] = $tsconf['emails.'];
		$this->settings['default'] = $tsconf['forms.']['default.'];
		$this->settings['registrationForm'] = $tsconf['forms.']['registrationForm.'];			
		$this->settings['confirmationForm'] = $tsconf['forms.']['confirmationForm.'];
		$this->settings['alreadyRegistered'] = $tsconf['forms.']['alreadyRegistered.'];
		$this->settings['confirmationMail'] = $tsconf['forms.']['confirmationMail.'];	
		$this->settings['notificationMail'] = $tsconf['forms.']['notificationMail.'];	
		$this->settings['eventList'] = $tsconf['forms.']['eventList.'];
		$this->settings['participantList'] = $tsconf['forms.']['participantList.'];
		
		//init userfields
		$this->settings['userfields'] = $tsconf['userfields.'];	
		
		//read the template file
		$this->settings['template'] = $this->cObj->fileResource($this->settings['template_file']);
		
		//clear event and registration
		unset($this->event);
		unset($this->registration);
		unset($this->event_obj);
		unset($this->event_fStart);
		unset($this->event_fEnd);
		unset($this->user);
	}

/***********************************************************************************************************************************************************************
 *
 * Getter and Setter Methods 
 *
 **********************************************************************************************************************************************************************/
	
	/*
         * Sets the event
         *
	 * @param	array		$event: Array with the event data
	 *
         * @return 	nothing
         */	
	public function setEvent($event) {
		$this->unsetEvent();
		$this->event = $event;
		
		//Get event object
		$tx_cal_phpicalendar_model = &t3lib_div::makeInstanceClassName('tx_cal_phpicalendar_model');
		$this->event_obj = &new $tx_cal_phpicalendar_model($event, FALSE, 'tx_cal_phpicalendar');

		//Prepare other event information
		$this->prepareFormatedDateTime();
		$this->getOrganizerData();
	}

	/*
         * Unsets the event
         *
         * @return 	nothing
         */
	public function unsetEvent() {
		unset($this->event);
		unset($this->event_obj);
		unset($this->location_obj);
		unset($this->organizer_obj);
		unset($this->event_fStart);
		unset($this->event_fEnd);
		unset($this->event_orgName);
		unset($this->event_orgEmail);
	}

	/*
         * Sets the registration
         *
	 * @param	array		$registration: Array with the registration data
	 *
         * @return 	nothing
         */	
	public function setRegistration($registration) {
		$this->unsetRegistration();
		$this->registration = $registration;
		$this->prepareFormatedDateTime();
	}

	/*
         * Unsets the registration
         *
         * @return 	nothing
         */
	public function unsetRegistration() {
		unset($this->registration);
		unset($this->event_fStart);
		unset($this->event_fEnd);
	}
	
	/*
         * Sets the user
         *
	 * @param	array		$user: Array with the user data
	 *
         * @return 	nothing
         */	
	public function setUser($user) {
		$this->unsetUser();
		$this->user = $user;
	}


	/*
         * Unsets the user
         *
         * @return 	nothing
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
	 * @param	array		@PresetSubpartMarker: Array containing preset subpart markers to use
	 * @param	array		@PresetMarker: Array containting preset markers to use
	 *
         * @return 	string		Rendered fields
         */	
	public function renderForm($templateSubpart, $confName, $mode, $templateSubpartSubpart='',$PresetSubpartMarker=Array(),$PresetMarker=Array()) {
		//get requested template subpart
		$template = $this->cObj->getSubpart($this->settings['template'],$templateSubpart);
		if ($templateSubpartSubpart !='') $template = $this->cObj->getSubpart($template, $templateSubpartSubpart);

		//get requested configuration
		$conf = $this->settings[$confName];
		
		//Replace subparts in the template
		foreach ($PresetSubpartMarker as $marker => $content) {
			$template = $this->cObj->substituteSubpart($template,$marker,$content);
		}
		$count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $template, $match);
		while ($count > 0) {
			$AllSubparts = array_unique($match[1]);
			foreach ($AllSubparts as $SingleSubpart) {
				$SubpartContent = $this->cObj->getSubpart($template, '###'.$SingleSubpart.'###');
				$SubpartContent = $this->applyWrap($SubpartContent, $conf, strtolower($SingleSubpart), $mode);
				$template = $this->cObj->substituteSubpart($template,'###'.$SingleSubpart.'###',$SubpartContent,0);
			}
			$count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $template, $match);
		}
		
		//Replace markers in template
		unset($fields);
		$count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $template, $match);
		while ($count > 0) {
			$marker = $PresetMarker;
			$AllMarkers = array_unique($match[1]);
			foreach ($AllMarkers as $SingleMarker) {
				$marker['###'.$SingleMarker.'###'] = $this->renderSingleMarker($SingleMarker, $conf, $mode);
			}
			$template = $this->cObj->substituteMarkerArray($template,$marker);
			$count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $template, $match);
		}
		return $template;
	}	
	
	public function renderSubject($confName) {
		return $this->applyWrap('', $this->settings[$confName],'subject','show');
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
		$config = $conf[$field.'.'];
		if (!isset($config)) $config = $this->settings['default'][$field.'.'];
		$config = isset($config['show.']) || isset($config['edit.']) ? $config[$mode.'.'] : $config;
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
		$value = ($mode=='edit') ? htmlspecialchars(isset($this->pi_base->piVars[$fieldname]) ? $this->pi_base->piVars[$fieldname] : $conf['default']) : htmlspecialchars($value);
		$caption = $conf['caption.'][$this->settings['language']] != '' ? $conf['caption.'][$this->settings['language']] : $conf['caption.']['default'];
		$field  = $this->cObj->stdWrap($value, $conf['layout.'][$mode.'.']);
		$marker = Array();
		$marker['###NAME###'] = htmlspecialchars($this->pi_base->prefixId.'['.'FIELD_'.$conf['name'].']');
		$marker['###CAPTION###'] = htmlspecialchars($caption);
		$field = $this->cObj->substituteMarkerArray($field,$marker);	
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
		$template = $this->cObj->getSubpart($this->settings['template'],'###SHOW_'.strtoupper($field['type']).'###');
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
		if ($mode=='show' && isset($this->registration['additional_data'])) {
			//Mode = Show: Render based on userfields in registration record
			$fieldsarray = unserialize($this->registration['additional_data']);
			if (is_array($fieldsarray)) {
				foreach ($fieldsarray as $name => $field) {
					if (isset($field['type'])) {
						$fields.=$this->renderOldUserField($field);
					} else {
						//"new" version
						$fields.= $this->renderUserField($field['conf'],$mode,$field['value']);
					}				
				}
			}
		} else if ($mode=='edit' && isset($this->settings['userfields'])){
			//Mode = Edit: Render fields based on userfields in TypoScript
			if (is_array($this->settings['userfields'])) {
				foreach ($this->settings['userfields'] as $field) {
					$fields.=$this->renderUserField($field,$mode);
				}
			}
			
			$hiddenfields = '';
			$calPiVars = t3lib_div::GParrayMerged('tx_cal_controller');
			foreach ($calPiVars as $name => $value) {
				$hiddenfields.='<input type="hidden" name="tx_cal_controller['.htmlspecialchars($name).']" value="'.htmlspecialchars($value).'" />';
			}
			$hiddenfields.='<input type="hidden" name="'.$this->pi_base->prefixId.'[cmd]" value="register" />';
			$hiddenfields.='<input type="hidden" name="no_cache" value="1" />';
			$fields.=$this->applyWrap($hiddenfields, $conf, 'submitbutton',$mode);
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
			//Render the userfields
			$fields = $this->renderUserFields($conf, $mode);
			$marker = $this->applyWrap($fields, $conf, 'fields',$mode);
			break;
		case 'LINK' :
			//Marker for the registration form
			$marker = $this->applyWrap(htmlspecialchars($this->pi_base->pi_linkTP_keepPIvars_url()),$conf, 'link',$mode);
			break;
		default :
			if (preg_match('/EVENT_([A-Z0-9_-])*/', $singleMarker)) {
				//Insert an event field. We have some special replacements here ...
				$fieldname = substr($singleMarker,6);
				switch ($fieldname) {
				case 'link' :
					$value = $this->getEventLink();
					break;
				case 'organizer_name' :
					$value = $this->event_orgName;
					break;
				case 'organizer_email' :
					$value = $this->event_orgEmail;
					break;
				case 'formated_start' :
					if (!isset($this->event_fStart)) $this->prepareFormatedDateTime();
					$value = $this->event_fStart;
					break;
				case 'formated_end' :
					if (!isset($this->event_fStart)) $this->prepareFormatedDateTime();
					$value = $this->event_fEnd;
					break;
				case 'start_date' :
					$value = $this->event_rStart->format($this->settings['date_format']);
					break;
				case 'end_date' :
					$value = $this->event_rEnd->format($this->settings['date_format']);
					break;
				case 'start_time' :
					$value = $this->event_obj->getStart()->format($this->settings['time_format']);
					break;
				case 'end_time' :
					$value = $this->event_obj->getEnd()->format($this->settings['time_format']);
					break;
				case 'teaser' :
				case 'description' :
					$value = isset($this->event[$fieldname]) ? $value = $this->event[$fieldname] : '';
					$value = $this->pi_base->pi_RTEcssText($value);
				default :
					$value = isset($this->event[$fieldname]) ? $value = $this->event[$fieldname] : '';
					break;
				}
			} else if (preg_match('/LOCATION_([A-Z0-9_-])*/', $singleMarker)) {
				//Insert a field from the location record
				$fieldname = substr($singleMarker,9);
				if (!isset($this->location_obj)) $this->setLocationObj();
				$value = isset($this->location_obj[$fieldname]) ? $value =$this->location_obj[$fieldname] : '';
			} else if (preg_match('/ORGANIZER_([A-Z0-9_-])*/', $singleMarker)) {
				//Insert a field from the organizer record
				$fieldname = substr($singleMarker,10);
				if (!isset($this->organizer_obj)) $this->setOrganizerObj();
				$value = isset($this->organizer_obj[$fieldname]) ? $value =$this->organizer_obj[$fieldname] : '';
			} else if (preg_match('/USER_([A-Z0-9_-])*/', $singleMarker)) {
				//Insert an user field
				$fieldname = substr($singleMarker,5);
				$value = isset($this->user[$fieldname]) ? $value =$this->user[$fieldname] : '';
			} else if (preg_match('/LABEL_([A-Z0-9_-])*/', $singleMarker)) {
				//Insert a label field
				$fieldname = 'label.'.str_replace('-','.',substr($singleMarker,6));
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
		$vars['tx_cal_controller[view]']='event';
		$vars['tx_cal_controller[type]']='tx_cal_phpicalendar';

		if (empty($this->registration)) {
			$vars['tx_cal_controller[getdate]']=intval($this->event['start_date']);
			$vars['tx_cal_controller[uid]']=intval($this->event['uid']);
		} else {
			$vars['tx_cal_controller[getdate]']=intval($this->registration['cal_event_getdate']);
			$vars['tx_cal_controller[uid]']=intval($this->registration['cal_event_uid']);
		}
		return $this->pi_base->pi_getPageLink($this->settings['eventpid'],'',$vars);
	}	
	
	/*
         * Prepare a formated startdate/-time and enddate/-time
	 * This also calculates the actual start- and enddate for
	 * recurring events.
	 *
         * @return 	nothing
         */	
	private function prepareFormatedDateTime() {
		if (isset($this->event)  && !isset($this->event_fStart)) {
			//format date and time
			$this->event_rStart = new tx_cal_date();
			$this->event_rStart->copy($this->event_obj->getStart());
			$this->event_rEnd = new tx_cal_date();
			$this->event_rEnd->copy($this->event_obj->getEnd());
			if ($this->event['freq'] != 'none' && !empty($this->registration)) {;
				$delta = (strtotime($this->registration['cal_event_getdate'])- strtotime($this->event['start_date']));
				$this->event_rStart->addSeconds($delta);
				$this->event_rEnd->addSeconds($delta);
			}
			$format = $this->event['allday'] == 0 ? $this->settings['date_format'].' '.$this->settings['time_format'] : $this->settings['date_format'];
			$this->event_fStart = $this->event_rStart->format($format);
			$this->event_fEnd =$this->event_rEnd->format($format);			
			if ($this->event['allday'] != 0 && $allday_string != '' and $formatedStart != $formatedEnd) {
				$this->event_fStart.=' '.$this->pi_base->pi_getLL('event_allday');
				$this->event_fEnd='';
			}
		}
	}

	function setOrganizerObj() {
		if (isset($this->event['organizer_id'])) {
			$select = '*';
			$table = 'tx_cal_organizer';
			$where = 'uid='.intval($this->event['organizer_id']).$this->cObj->enableFields('tx_cal_organizer');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$this->organizer_obj = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
	}
	function setLocationObj() {
		if (isset($this->event['location_id'])) {
			$select = '*';
			$table = 'tx_cal_location';
			$where = 'uid='.intval($this->event['location_id']).$this->cObj->enableFields('tx_cal_location');
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$this->location_obj = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
	}

	/*
         * Provide the name of the organizer for an event
	 *
         * @return 	nothing
         */	
	function getOrganizerData() {
		if ($this->event['organizer_id']==0) {
			//Organizer in event record, email not available
			$name = $this->event['organizer'];
			$email ='';
		} else {
			//Organizer and email in separate organizer record
			$select = 'name, email';
			$table = 'tx_cal_organizer';
			$where = 'uid='.intval($this->event['organizer_id']);
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$this->event_orgName = $row['name'];
			$this->event_orgEmail = $row['email'];
			$this->settings['organizer_email'] = $row['email'];
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/user/class.tx_register4cal_render.php'])      {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/user/class.tx_register4cal_render.php']);
}
        
?>