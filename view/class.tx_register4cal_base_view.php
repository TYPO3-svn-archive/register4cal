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
 * class.tx_register4cal_base_view.php
 *
 * Class to implement a basic view class
 *
 * $Id$
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Class to implement a basic view class
 *
 * @author	Thomas Ernst <typo3@thernst.de>
 * @package	TYPO3
 * @subpackage	tx_register4cal
 */
class tx_register4cal_base_view extends tslib_pibase {
    /* =========================================================================
     * pibase settings
     * ========================================================================= */

    var $prefixId = 'tx_register4cal_view';  // Same as class name
    var $scriptRelPath = 'view/class.tx_register4cal_base_view.php';   // Path to this script relative to the extension dir.
    var $extKey = 'register4cal';   // The extension key.
    var $pi_checkCHash = true;

    /* =========================================================================
     * Private variables
     * ========================================================================= */

    /**
     * Instance of tx_register4cal_settings model, containing extension settings
     * @var tx_register4cal_settings
     */
    protected $settings;

    /**
     * Current configuration for requested form
     * @var Array;
     */
    protected $config;

    /**
     * Instance of tx_register4cal_registration model, containing registration data
     * @var tx_register4cal_registration
     */
    protected $registration;

    /**
     * Template subpart to work on
     * @var String
     */
    protected $template;

    /**
     * Name of current configuration
     * @var string 
     */
    protected $confName;

    /* =========================================================================
     * Public static methods
     * ========================================================================= */

    /**
     * Render fatal error message
     * @param string $error error message
     * @return string HTML coding for error message
     */
    public static function renderError($error) {
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['register4cal']);
        if ($confArr['hideErrorsInFrontend']) {
            $content = '';
        } else {
            $content = '<div style="border:2px solid red;width:100%;padding:2px;margin_2px;background-color:red;">' .
                    '<span style="font-size:x-large;color:yellow;">Extension register4cal: Error</span>' .
                    '<p style="background-color:white;padding:1em;">' . $error . '</p>' .
                    //'<p style="background-color:white;padding:1em;">'.t3lib_div::debug_trail().'</p>'.
                    '</div>';
        }
        return $content;
    }

    /* =========================================================================
     * Constructor and static getInstance() methid
     * ========================================================================= */

    /**
     * Create an instance of the class while taking care of the different ways
     * to instanciace classes having constructors with parameters in different
     * Typo3 versions
     * @return tx_register4cal_base_view
     */
    public static function getInstance() {
        if (tx_register4cal_static::getTypo3IntVersion() <= 4003000) {
            $className = &t3lib_div::makeInstanceClassName('tx_register4cal_base_view');
            $class = new $className($renderDisplayOnly);
        } else {
            $class = &t3lib_div::makeInstance('tx_register4cal_base_view');
        }
        return $class;
    }

    /**
     * Class constructor
     * @global tslib_fe $TSFE
     * @param boolean $renderDisplayOnly Flag: Render display only (even if status requires input rendering)
     */
    public function __construct() {
        global $TSFE;
        
        if (tx_register4cal_static::getTypo3IntVersion()>=4006000) {
            parent::__construct();
        } else {
          parent::tslib_pibase();  // Deprecated since Typo3 4.6
        }        
        
        $this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.'];
        $this->pi_loadLL();
        $this->cObj = $TSFE->cObj;

        // get settings
        require_once(t3lib_extMgm::extPath('register4cal') . 'model/class.tx_register4cal_settings.php');
        $this->settings = tx_register4cal_settings::getInstance();
    }

    /* =========================================================================
     * Properties (get and set)
     * ========================================================================= */

    /**
     * Sets the registration object to use
     * @param tx_register4cal_registration $registration Registration ut use
     */
    public function setRegistration($registration) {
        $this->registration = $registration;
    }
 
    /* =========================================================================
     * Public methods
     * ========================================================================= */

    /**
     * Load template, etc based on config section. The config section needs to 
     * be defined as subsection of plugin.tx_register4cal_pi1.forms.
     * @param string $confName name of config section to use 
     */
    public function load($confName) {
        // get requested configuration
        $this->config = $this->settings->formConfig($confName);

        // Start of compatibility code =========================================
        if ($this->config['subtemplate'] == '') {
            $tempConfig = $this->settings->formConfig(strtolower($confName));
            if ($tempConfig['subtemplate'] != '') {
                $this->config = $tempConfig;
                $confName = strtolower($confName);
            }
        }
        // End of compatibility code ===========================================

        if (!isset($this->config))
            throw new Exception('Configuration "' . $confName . '" not set!');
        $this->confName = $confName;

        // get requested template subpart
        $templateFile = $this->cObj->fileResource($this->settings->templateFile);
        if (!$templateFile)
            throw new Exception('Template file "' . $this->settings->templateFile . '" not existing or empty!');
        if (!isset($this->config['subtemplate']))
            throw new Exception('Configuration "' . $confName . '" does not contain an element "subtemplate"!');
        $templateSubpartName = '###' . $this->config['subtemplate'] . '###';
        $template = $this->cObj->getSubpart($templateFile, $templateSubpartName);
        if (!$template)
            throw new Exception('Template file "' . $this->settings->templateFile . '" does not contain a subpart "' . $this->config['subtemplate'] . '"!');
        $this->template = $template;
    }

    /**
     * Replace subpart with given content
     * @param String $subpartName Name of subpart to replact
     * @param String $subpartContent Content for subpart
     */
    public function replaceSubpart($subpartName, $subpartContent) {
        $this->template = $this->cObj->substituteSubpart($this->template, '###' . $subpartName . '###', $subpartContent, 0);
    }

    /**
     * Render subpart of template and return result
     * @param String $subpartName Name of subpart
     * @return <type> Subpart content
     */
    public function renderSubpart($subpartName) {
        $content = $this->cObj->getSubpart($this->template, '###' . $subpartName . '###');
        // replace remaining subparts
        $count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $content, $match);
        while ($count > 0) {
            $allSubparts = array_unique($match[1]);
            foreach ($allSubparts as $singleSubpart) {
                $subpartContent = $this->cObj->getSubpart($content, '###' . $singleSubpart . '###');
                $subpartContent = $this->applyWrap(strtolower($singleSubpart), $subpartContent);
                $content = $this->cObj->substituteSubpart($content, '###' . $singleSubpart . '###', $subpartContent, 0);
            }
            $count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $content, $match);
        }

        // replace markers in template
        $marker = Array();
        $count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $content, $match);
        while ($count > 0) {
            $allMarkers = array_unique($match[1]);
            foreach ($allMarkers as $singleMarker) {
                if (!isset($marker['###' . $singleMarker . '###']))
                    $marker['###' . $singleMarker . '###'] = $this->renderSingleMarker($singleMarker);
            }
            $content = $this->cObj->substituteMarkerArray($content, $marker);
            $count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $content, $match);
        }
        $content = $this->applyWrap(strtolower($singleSubpart), $content);
        return $content;
    }

    /**
     * Render template and return result
     * @param Array $presetSubpartMarker Array containing predefined subpart content
     * @param Array $predefinedMarkers  Array containing predefined markers
     * @return Content
     */
    public function render($presetSubpartMarker = Array(), $predefinedMarkers = Array()) {
        // replace preset subparts in the template
        foreach ($presetSubpartMarker as $marker => $content) {
            $this->template = $this->cObj->substituteSubpart($this->template, $marker, $content);
        }

        // replace remaining subparts
        $count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $this->template, $match);
        while ($count > 0) {
            $allSubparts = array_unique($match[1]);
            foreach ($allSubparts as $singleSubpart) {
                $subpartContent = $this->renderSubpart($singleSubpart);
                $this->template = $this->cObj->substituteSubpart($this->template, '###' . $singleSubpart . '###', $subpartContent, 0);
            }
            $count = preg_match_all('!\<\!--[a-zA-Z0-9 ]*###([A-Z0-9_-|]*)\###[a-zA-Z0-9 ]*-->!is', $this->template, $match);
        }

        // replace markers in template
        $marker = $presetMarker;
        $count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $this->template, $match);
        while ($count > 0) {
            $allMarkers = array_unique($match[1]);
            foreach ($allMarkers as $singleMarker) {
                if (!isset($marker['###' . $singleMarker . '###']))
                    $marker['###' . $singleMarker . '###'] = $this->renderSingleMarker($singleMarker);
            }
            $this->template = $this->cObj->substituteMarkerArray($this->template, $marker);
            $count = preg_match_all('!\###([A-Z0-9-_-|]*)\###!is', $this->template, $match);
        }

        return $this->template;
    }

    /**
     * Render template and send result via email
     * @param string/array email address(es) of recipient(s)
     * @param Array $presetSubpartMarker Array containing predefined subpart content
     * @param Array $predefinedMarkers  Array containing predefined markers
     * @param Array $attachments Array containing attachments for Email
     */
    public function sendMail($recipientAddresses, $presetSubpartMarker = Array(), $predefinedMarkers = Array(), $attachments = Array()) {
        $subject = $this->applyWrap('subject');
        $content = $this->render($presetSubpartMarker, $predefinedMarkers);

        //send email
        if (tx_register4cal_static::getTypo3IntVersion() < 4005000) {
            // Before Typo3 4.5: Use htmlmail
            $htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
            $htmlmail->start();
            $htmlmail->subject = $subject;
            $htmlmail->from_email = $this->settings->mailSenderAddress;
            $htmlmail->CharSet = 'UTF-8';
            $htmlmail->from_name = $this->settings->mailSenderName;
            $htmlmail->replyto_email = $htmlmail->from_email;
            $htmlmail->replyto_name = $htmlmail->from_name;

            /* For unknown reasons, attaching files is not working in t3lib_htmlmail
             * The following coding has been commented out therefore. 
             * Attaching files is not working below Typo3 4.5.

              foreach ($attachments as $attachment) {
              if (is_array($attachment)) {
              $htmlmail->theParts['attach'][] = $attachment;
              } else {
              $htmlmail->addAttachment($attachment);
              }
              t3lib_div::debug($htmlmail->theParts['attach']);
              }
             */

            $htmlmail->setPlain($this->html2text($content));
            $htmlmail->setHtml($content);
            $htmlmail->setHeaders();
            $htmlmail->setContent();
            $htmlmail->setRecipient($recipientAddresses);
            $htmlmail->sendTheMail();
        } else {
            // From Typo3 4.5 on: Use swiftmailer
            $mail = t3lib_div::makeInstance('t3lib_mail_Message');
            $mail->setFrom(array($this->settings->mailSenderAddress => $this->settings->mailSenderName));
            $mail->setTo($recipientAddresses);
            $mail->setSubject($subject);
            $mail->setBody($content, 'text/html');
            $mail->addPart($this->html2text($content), 'text/plain');

            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $obj = Swift_Attachment::newInstance($attachment['content'], $attachment['filename'], $attachment['content_type']);
                } else {
                    $obj = Swift_Attachment::fromPath($attachment);
                }
                $mail->attach($obj);
            }

            $mail->send();
        }
    }

    /* =========================================================================
     * Protected methods
     * ========================================================================= */

    /**
     * Apply stdWrap to a value
     * @param string $field: Fieldname to determine stdWrap-Configuration
     * @param string $value: Value to wich stdWrap should be applied
     * @return string Value with stdWrap applied (if field contained in config)
     */
    protected function applyWrap($field, $value = '') {
        $field = strtolower($field) . '.';
        if (isset($this->config[$field])) {
            return $this->cObj->stdWrap($value, $this->config[$field]);
        } else {
            return $value;
        }
    }

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
            case 'LINK' :
                // Marker for the registration form
                $marker = $this->applyWrap('link', htmlspecialchars($this->pi_getPageLink($TSFE->id)));
                break;
            case 'STATUS' :
                $value = $this->pi_getLL('label_status_' . intval($this->registration->getRegistrationField('status')));
                $marker = $this->applyWrap('status', $value);
                break;
            case 'MAXATTENDEES':
                $value = $this->registration->getEventField('tx_register4cal_maxattendees');
                if ($value == 0)
                    $value = $this->pi_getLL('label_unlimited');
                $marker = $this->applyWrap('maxattendees', $value);
                break;
            case 'NUMATTENDEES':
                $value = $this->registration->getEventField('tx_register4cal_numregistered');
                $marker = $this->applyWrap('numattendees', $value);
                break;
            case 'NUMFREE':
                $numfree = $this->registration->getEventField('tx_register4cal_numfree');
                $value = $numfree == 0 ? $this->pi_getLL('label_unlimited') : $numfree;
                $marker = $this->applyWrap('numfree', $value);
                break;
            case 'NUMWAITLIST':
                $value = $this->registration->getEventField('tx_register4cal_numwaitlist');
                $marker = $this->applyWrap('numwaitlist', $value);
                break;
            default :
                if (preg_match('/EVENT_([A-Z0-9_-])*/', $singleMarker)) {
                    // Insert an event field. Special fields have been set during loading the event in tx__register4cal_registration
                    $field = substr($singleMarker, 6);
                    $value = $this->registration->getEventField($field);
                } elseif (preg_match('/LOCATION_([A-Z0-9_-])*/', $singleMarker)) {
                    // Insert a field from the location record
                    $field = substr($singleMarker, 9);
                    $value = $this->registration->getLocationField($field);
                } elseif (preg_match('/ORGANIZER_([A-Z0-9_-])*/', $singleMarker)) {
                    // Insert a field from the organizer record
                    $field = substr($singleMarker, 10);
                    $value = $this->registration->getOrganizerField($field);
                } elseif (preg_match('/USER_([A-Z0-9_-])*/', $singleMarker)) {
                    // Insert an user field. Special fields have been set during loading the user in tx__register4cal_registration
                    $field = substr($singleMarker, 5);
                    $value = $this->registration->getUserField($field);
                } elseif (preg_match('/UDEF_([A-Z0-9_-])*/', $singleMarker)) {
                    if (preg_match('/LABEL_UDEF_([A-Z0-9_-])*/', $singleMarker)) {
                        // Insert a label from the user defined fields
                        $field = substr($singleMarker, 11);
                        $array = $this->registration->getUserdefinedField($field);
                        $value = is_array($array) ? $array['conf']['caption'] : '';
                    } else {
                        // Insert a field from the user defined fields
                        $field = substr($singleMarker, 5);
                        $array = $this->registration->getUserdefinedField($field);
                        $value = is_array($array) ? $array['value'] : '';
                    }
                } elseif (preg_match('/LABEL_([A-Z0-9_-])*/', $singleMarker)) {
                    // Insert a label field. 
                    $fieldname = 'label_' . substr($singleMarker, 6);
                    $value = $this->pi_getLL($fieldname);
                } else {
                    $value = '';
                }
                $marker = $this->applyWrap($singleMarker, $value);
                break;
        }
        return $marker;
    }

    /**
     * Privides several hidden fields
     * - cal settings
     * - noCache if required
     * - register4cal-cmd if required
     * @param integer $noCache if set to 1, a noCache=1 field is added
     * @param string $cmd if set, a register4cal-cmd field with this value is added
     * @return string 
     */
    protected function getHiddenFields($noCache = 0, $cmd = '', $other = Array()) {
        $calPiVars = t3lib_div::_GPmerged('tx_cal_controller');
        foreach ($calPiVars as $name => $value) {
            $hiddenFields .= '<input type="hidden" name="tx_cal_controller[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '" />';
        }
        if ($cmd)
            $hiddenFields .= '<input type="hidden" name="' . $this->prefixId . '[cmd]" value="' . htmlspecialchars($cmd) . '" />';
        foreach ($other as $name => $value)
            $hiddenFields .= '<input type="hidden" name="' . $this->prefixId . '[' . $name . ']" value="' . htmlspecialchars($value) . '" />';
        if ($noCache == 1)
            $hiddenFields .= '<input type="hidden" name="no_cache" value="1" />';
        return $hiddenFields;
    }

    /**
     * Provides an array with all parameters set for cal
     * @return Array with tx_cal_controller[name] => value elements
     */
    protected function getCalParams() {
        $calParams = Array();
        $calPiVars = t3lib_div::_GPmerged('tx_cal_controller');
        foreach ($calPiVars as $name => $value) {
            $calParams['tx_cal_controller[' . htmlspecialchars($name) . ']'] = htmlspecialchars($value);
        }
        return $calParams;
    }

    // strip javascript, styles, html tags, normalize entities and spaces
    // based on http://www.php.net/manual/en/function.strip-tags.php#68757
    // ThER170213: Coding from http://snipplr.com/view.php?codeview&id=57982
    private function html2text($html) {
        $text = $html;
        static $search = array(
    '@<script.+?</script>@usi', // Strip out javascript content
    '@<style.+?</style>@usi', // Strip style content
    '@<!--.+?-->@us', // Strip multi-line comments including CDATA
    '@</?[a-z].*?\>@usi', // Strip out HTML tags
        );
        $text = preg_replace($search, ' ', $text);
        // normalize common entities
        $text = $this->normalizeEntities($text);
        // decode other entities
        $text = html_entity_decode($text, ENT_QUOTES, 'utf-8');
        // normalize possibly repeated newlines, tabs, spaces to spaces
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        // we must still run htmlentities on anything that comes out!
        // for instance:
        // <<a>script>alert('XSS')//<<a>/script>
        // will become
        // <script>alert('XSS')//</script>
        return $text;
    }

    // replace encoded and double encoded entities to equivalent unicode character
    private function normalizeEntities($text) {
        static $find = array();
        static $repl = array();
        if (!count($find)) {
            // build $find and $replace from map one time
            $map = array(
                array('\'', 'apos', 39, 'x27'), // Apostrophe
                array('\'', '‘', 'lsquo', 8216, 'x2018'), // Open single quote
                array('\'', '’', 'rsquo', 8217, 'x2019'), // Close single quote
                array('"', '“', 'ldquo', 8220, 'x201C'), // Open double quotes
                array('"', '”', 'rdquo', 8221, 'x201D'), // Close double quotes
                array('\'', '‚', 'sbquo', 8218, 'x201A'), // Single low-9 quote
                array('"', '„', 'bdquo', 8222, 'x201E'), // Double low-9 quote
                array('\'', '′', 'prime', 8242, 'x2032'), // Prime/minutes/feet
                array('"', '″', 'Prime', 8243, 'x2033'), // Double prime/seconds/inches
                array(' ', 'nbsp', 160, 'xA0'), // Non-breaking space
                array('-', '‐', 8208, 'x2010'), // Hyphen
                array('-', '–', 'ndash', 8211, 150, 'x2013'), // En dash
                array('--', '—', 'mdash', 8212, 151, 'x2014'), // Em dash
                array(' ', ' ', 'ensp', 8194, 'x2002'), // En space
                array(' ', ' ', 'emsp', 8195, 'x2003'), // Em space
                array(' ', ' ', 'thinsp', 8201, 'x2009'), // Thin space
                array('*', '•', 'bull', 8226, 'x2022'), // Bullet
                array('*', '‣', 8227, 'x2023'), // Triangular bullet
                array('...', '…', 'hellip', 8230, 'x2026'), // Horizontal ellipsis
                array('°', 'deg', 176, 'xB0'), // Degree
                array('€', 'euro', 8364, 'x20AC'), // Euro
                array('¥', 'yen', 165, 'xA5'), // Yen
                array('£', 'pound', 163, 'xA3'), // British Pound
                array('©', 'copy', 169, 'xA9'), // Copyright Sign
                array('®', 'reg', 174, 'xAE'), // Registered Sign
                array('™', 'trade', 8482, 'x2122') // TM Sign
            );
            foreach ($map as $e) {
                for ($i = 1; $i < count($e); ++$i) {
                    $code = $e[$i];
                    if (is_int($code)) {
                        // numeric entity
                        $regex = "/&(amp;)?#0*$code;/";
                    } elseif (preg_match('/^.$/u', $code)/* one unicode char */) {
                        // single character
                        $regex = "/$code/u";
                    } elseif (preg_match('/^x([0-9A-F]{2}){1,2}$/i', $code)) {
                        // hex entity
                        $regex = "/&(amp;)?#x0*" . substr($code, 1) . ";/i";
                    } else {
                        // named entity
                        $regex = "/&(amp;)?$code;/";
                    }
                    $find[] = $regex;
                    $repl[] = $e[0];
                }
            }
        } // end first time build
        return preg_replace($find, $repl, $text);
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_base_view.php']) {
    include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/view/class.tx_register4cal_base_view.php']);
}
?>
