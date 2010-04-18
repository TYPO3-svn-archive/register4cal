<?php

/***************************************************************
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class for updating register4cal configuration from TypoScript to database.
 *
 *
 * @author  Thomas Ernst <typo3@thernst.de>
 * @package TYPO3
 * @subpackage register4cal
 */
class ext_update {
	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main() {
		$out .= '<a href="' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '">' . $this->lang('s0.restartlink') . '</a><br>';

		$func = trim(t3lib_div::_GP('func'));
		switch ($func) {
			case 'step3':
				$out .= $this->doStep3();
				break;
			case 'step2':
				$out .= $this->doStep2();
				break;
			case 'step1':
				// fall through 
			default:
				$out = $this->doStep1();
				break;
		}
		
		return $out;

	}

	function lang($index) {
		return $GLOBALS['LANG']->sL('LLL:EXT:register4cal/locallang_update.xml:' . $index);
	}

	function doStep1($error = '') {
		$conf = $this->getExtConf();

			// Let's see if we have some userfields in TS
		if  (!isset($conf['userfields.'])) {
			return $this->displayError($this->lang('error.nofields'));
		}
		
			// Check if userfields are already defined in the new tables
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('count(*) as num','tx_register4cal_fields','deleted = 0 AND sys_language_uid in (0,-1)');
		if (isset($rows)) {
			if ($rows[0]['num'] != 0)
				return $this->displayError($this->lang('error.alreadydefined'));
		}

			// display error, if set
		if ($error != '') $out .= $this->displayError($error);

			// show existing customizing
		$out .= '<h3>' . $this->lang('s1.currentConf.head') . '</h3><p>' . $this->lang('s1.currentConf.text') . '</p><p>&nbsp;</p>';	
		$arr = Array();
		$arr[] = 'plugin.tx_register4cal_pi1.userfields {';
		$this->getConfigText($conf['userfields.'],0,$arr);
		$arr[] = '}';
		$out .= '<p style="border:1px solid black;font-family:monospace;height:300px;overflow:scroll;">'.nl2br(implode(chr(13).chr(10),$arr)).'</p>';
		
			// parse customizing		
		$fields = $this->parseUserfields($conf['userfields.'],$languages);
		
			// languages
		$out .= '<h3>' . $this->lang('s1.languages.head') . '</h3><p>' . $this->lang('s1.languages.text') . '</p><p>&nbsp;</p>';
		$out .= '<form action = "' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '" method="post">' .
			'<table border="1" style="border-collapse:collapse">' .
			'<tr><th>' . $this->lang('s1.languages.col1') . '</th><th>' . $this->lang('s1.languages.col2') . '</th></tr>';
		foreach ($languages as $language => $languid) {
			$out .= '<tr><td>' . $language . '</td>' .
				'<td><input type="text" value = "' . $languid . '" name="sys_language_uid[' . $language . ']" /></td></tr>';
		}
		$out .= '</table>';
		
			// target page
		$out .= '<h3>' . $this->lang('s1.targetPid.head') . '</h3><p>' . $this->lang('s1.targetPid.text') . '</p><p>&nbsp;</p>' .
			'PID: <input type="text" name="pid" value="1" size=4 /><br /><br />' .
			'<input type="hidden" name="fields" value="' . urlencode(serialize($fields)) . '" />' .
			'<input type="hidden" name="func" value="step2" />' .
			'<input type="submit" value="' . $this->lang('s1.submit') . '" />' .
			'</form>';
		return $out;		
	}

	function doStep2() {
		$conf = $this->getExtConf();
		
			// get and show language settings
		$langids = t3lib_div::_GP('sys_language_uid');
		asort($langids);
		$found = false;
		foreach($langids as $lang => $uid) {
			if ($uid==0) {
				$found = true;
				break;
			}
		}
		if (!$found) return $this->doStep1($this->lang('error.nolangid0'));
		$out .= '<p>'.$first.'</p>';
		$out .= '<h3>' . $this->lang('s2.languages.head') . '</h3><p>' . $this->lang('s2.languages.text') . '</p>'.
			'<table border="1" style="border-collapse:collapse">' .
			'<tr><th>' . $this->lang('s2.languages.col1') . '</th><th>' . $this->lang('s2.languages.col2') . '</th></tr>';
		foreach ($langids as $language => $languid) $out .= '<tr><td>' . $language . '</td><td>' . $languid . '</td></tr>';
		$out .= '</table>';
		
			// get and show target page settings
		$pid = intval(t3lib_div::_GP('pid'));
		if ($pid==0) return $this->doStep1($this->lang('error.nopid'));
		$out .= '<h3>' . $this->lang('s2.targetPid.head') . '</h3><p>' . str_replace('###NUM###', $pid, $this->lang('s2.targetPid.text')) . '</p>';

			// get field data
		$fields = t3lib_div::_GP('fields');
		$fields = unserialize(urldecode($fields));
		
		$out .= '<h3>' . $this->lang('s2.fieldconfig.head') . '</h3><p>' . $this->lang('s2.fieldconfig.text') . '</p>'.
			'<form action = "' . t3lib_div::linkThisScript(array('do_update' => '', 'func' => '')) . '" method="post">' .
			'<table border=1 style="border-collapse:collapse;">';
		$out .= '<tr><th>&nbsp;</th><th>' . $this->lang('s2.fieldconfig.col1') . '</th><th>' . $this->lang('s2.fieldconfig.col2') . '</th><th>' . $this->lang('s2.fieldconfig.col3') . '</th>' .
			'<th>' . $this->lang('s2.fieldconfig.col4') . '</th><th>' . $this->lang('s2.fieldconfig.col5') . '</th><th>' . $this->lang('s2.fieldconfig.col6') . '</th>' .
			'<th>' . $this->lang('s2.fieldconfig.col7') . '</th><th>' . $this->lang('s2.fieldconfig.col8') . '</th></tr>';
		foreach($fields as $field) {
			$rows = 0;
			$outfield = '';
			foreach($langids as $lang => $uid) {
				if ($uid != '') {
					$rows++;
					$outfield .= '<tr>';
					if ($rows == 1) {
						$outfield .= '<td rowspan = "###ROWS###" style="width:10px;">&nbsp;</td>';
						$outfield .= '<td>' . $uid . '</td>';
						$outfield .= '<td>' . $field['name'] . '</td>';
						$outfield .= '<td>' . $field['type'] . '</td>';
						$outfield .= '<td>' . $field['caption'][$lang] . '</td>';
						$outfield .= '<td>' . $field['default'] . '</td>';
						$outfield .= '<td>' . $field['height'] . '</td>';
						$outfield .= '<td>' . $field['width'] . '</td>';
						$outfield .= '<td>' . $field['options'][$lang]  . '</td>';
					} else {
						$outfield .= '<td>' . $uid . '</td>';
						$outfield .= '<td>&nbsp;</td>';
						$outfield .= '<td>&nbsp;</td>';
						$outfield .= '<td>' . $field['caption'][$lang] . '</td>';
						$outfield .= '<td>&nbsp;</td>';
						$outfield .= '<td>&nbsp;</td>';
						$outfield .= '<td>&nbsp;</td>';
						$outfield .= '<td>' . $field['options'][$lang]  . '</td>';
					}
					$outfield .= '</tr>';
				}
			}
			$out .= str_replace('###ROWS###', $rows, $outfield);
		}
		$out .= '</table>';
		
		$out .= '<h3>' . $this->lang('s2.fieldgroups.head') . '</h3><p>' . $this->lang('s2.fieldgroups.text') . '</p><p>&nbsp;</p>';
		
			// hidden fields and submit button
		$fields = urlencode(serialize($fields));
		$lang = urlencode(serialize($langids));
		$check = md5($fields . $pid . $lang);
		$out .=	'<input type="hidden" name="fields" value="' . $fields . '" />' .
			'<input type="hidden" name="lang" value="' . $lang . '" />' .
			'<input type="hidden" name="pid" value="' . $pid . '" />' .
			'<input type="hidden" name="check" value="' . $check . '" />' .
			'<input type="hidden" name="func" value="step3" />' .
			'<input type="submit" value="' . $this->lang('s2.submit') . '" />' .
			'</form>';
			
		return $out;
	}

	function doStep3() {
			// get data and check integrity
		$fields = t3lib_div::_GP('fields');
		$langs = t3lib_div::_GP('lang');
		$pid = intval(t3lib_div::_GP('pid'));
		$check = t3lib_div::_GP('check');
		if ($check != md5($fields . $pid . $langs)) return $this->doStep1($this->lang('error.data'));
		
		$fields = unserialize(urldecode($fields));
		$langs = unserialize(urldecode($langs));
		
			// do the update statements
		$out .= '<h3>' . $this->lang('s3.fieldconfig.head') .'</h>'.
		$num=0;
		$fieldlist = Array();
		foreach($fields as $field) {
			$rows = 0;
			$parent = 0;
			$GLOBALS['TYPO3_DB']->debugOutput = true;
			foreach($langs as $lang => $uid) {
				if ($uid != '') {
					$rows++;
					$insert = Array();
					$insert['pid'] = $pid;
					$insert['tstamp'] = time;
					$insert['crdate'] = time;
					$insert['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
					$insert['sys_language_uid'] = $uid;
					$insert['options'] = $field['options'][$lang];
					$insert['caption'] = $field['caption'][$lang];
					if ($uid==0) {
						$insert['name'] = $field['name'];
						$insert['type'] = $field['type'];
						$insert['width'] = $field['width'];
						$insert['height'] = $field['height'];
						$insert['defaultvalue'] = $field['default'];
					} else {
						$insert['l10n_parent'] = $parent;
					}
					$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_register4cal_fields', $insert);
					$err = $GLOBALS['TYPO3_DB']->sql_error($res);
					if ($err) {
						$out .= $this->lang('s3.error') . ': '.$err;
					} else {
						if ($uid==0) {
							$parent = $GLOBALS['TYPO3_DB']->sql_insert_id();
							$fieldlist[] = $parent;
						}
						$num++;
					}
				}
			}
		}
		$out .= '<p>' . str_replace('###NUM###', $num, $this->lang('s3.fieldconfig.text')) . '</p>';
		
		$out .= '<h3>' .$this->lang('s3.fieldgroups.head') .'</h>'.
		$insert = Array();
		$insert['pid'] = $pid;
		$insert['tstamp'] = time;
		$insert['crdate'] = time;
		$insert['cruser_id'] = $GLOBALS['BE_USER']->user['uid'];
		$insert['name'] = 'Default';
		$insert['fields'] = implode(',',$fieldlist);
		$insert['isdefault'] = 1;
		$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_register4cal_fieldsets', $insert);
		$err = $GLOBALS['TYPO3_DB']->sql_error($res);
		if ($err) {
			$out .= $this->lang('s3.error') . ': '.$err;
		} else {
			$out .= '<p>' . $this->lang('s3.fieldgroups.text') . '</p>';
		}
		return $out;
	}

	function getConfigText($data, $level = 0, &$out) {
		$spaces = str_repeat('&nbsp;',($level+1)*4);
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				//t3lib_div::debug($value, $key);
				if (substr($key,-1) == '.') {
					$out[] = $spaces . substr($key, 0, strlen($key) - 1) . ' {';
					$this->getConfigText($value, $level + 1, $out);
					$out[] = $spaces . '}';
				} else {
					$out[] =  $spaces . $key . ' = ' . htmlspecialchars($value);
				}
			}
		}
	}

	/*
	 * Gets the register4cal typoscript configuration 
	 *
	 * return	array		Configuration array from TS
	 */
	function getExtConf() {
		// We need to create our own template setup if we are in the BE
		// and we aren't currently creating a DirectMail page.
		if ((TYPO3_MODE == 'BE') && !is_object($GLOBALS['TSFE'])) {
			$template = t3lib_div::makeInstance('t3lib_TStemplate');
			// do not log time-performance information
			$template->tt_track = 0;
			$template->init();
				// Get the root line
			$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
			// the selected page in the BE is found
			// exactly as in t3lib_SCbase::init()
			$rootline = $sys_page->getRootLine(1);
				// This generates the constants/config + hierarchy info for the template.
			$template->runThroughTemplates($rootline, 0);
			$template->generateConfig();
			$conf = $template->setup['plugin.']['tx_register4cal_pi1.'];
		} else {
			// On the front end, we can use the provided template setup.
			$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_register4cal_pi1.'];
		}
		return $conf;
	}

	/*
	 * Parse trough userfields and create data for new fields array
	 *
	 * @param	array		$userfields: 'userfields.'-Element from TS config
	 * @param	array		$lang: Array containing all languages used in userfield definition
	 * @return	array		Array containing parsed field data
	 */
	function parseUserfields($userfields, &$languages) {
		$fields = Array();
		$languages = Array();
		foreach($userfields as $userfield) {
			$field = Array();
				// Basic data
			$field['name'] = $userfield['name'];
			$field['default'] = $userfield['default'];
				// Captions
			$captions = Array();
			foreach($userfield['caption.'] as $lang => $caption) {
				$lang = $lang == 'default' ? 'en' : $lang;
				$languages[$lang] = 1;
				$captions[$lang] = $caption;
			}
			$field['caption'] = $captions;
				// Options
			if (is_array($userfield['options.'])) {
				$optarray = Array();
				$optlang = Array();
				foreach($userfield['options.'] as $useroption) {
					$options = Array();
					foreach($useroption as $lang => $option) {
						$lang = $lang == 'default' ? 'en' : $lang;
						$languages[$lang] = 1;
						$options[$lang] = $option;
						$optlang[$lang][] = $option;
					}
					$optarray[] = $options;
				}
			}
			
				// Now parse layout.edit.datawrap to get additional information
			$dataWrap = $userfield['layout.']['edit.']['dataWrap'];
			$tags = $this->parseHtml($dataWrap);
			
			$found = false;
			if (is_array($tags['input']) && !$found) {
				foreach($tags['input'] as $tag) {
					if ($tag['type'] == 'text') {
						// Simple text field
						$field['type'] = 1;
						$field['width'] = $tag['size'];
						$found = true;
						break;
					}
				}
			}
			if (is_array($tags['textarea']) && !$found) {
				// Multiline text field
				$tag = $tags['textarea'][0];
				$field['type'] = 2;
				$field['width'] = $tag['cols'];
				$field['height'] = $tag['rows'];
				$found = true;
			}
			if (is_array($tags['select']) && !$found) {
				// Option
				$tag = $tags['select'][0];
				$field['type'] = 3;
				$field['height'] = $tag['size'];
				//$field['optarray'] = $optarray;
				foreach($optlang as $lang=>$opt) $field['options'][$lang] = implode('|',$opt);
				$found = true;
			}
			
			$arr = Array();
			foreach ($languages as $language => $used) {
				//$language = $language=='default' ? 'en' : $language;
				$arr[$language] = count($arr);
			}
			$languages = $arr;
			$fields[$field['name']] = $field;
		}
		
		return $fields;
	}

	/*
	 * parseHtml.php
	 * Author: Carlos Costa Jordao
	 * Email: carlosjordao@yahoo.com
	 *
	 * My notation of variables:
	 * i_ = integer, ex: i_count
	 * a_ = array, a_html
	 * b_ = boolean,
	 * s_ = string
	 *
	 * What it does:
	 * - parses a html string and get the tags
 	 * - exceptions: html tags like <br> <hr> </a>, etc 
	 * - At the end, the array will look like this:
	 * ["IMG"][0]["SRC"] = "xxx"
	 * ["IMG"][1]["SRC"] = "xxx"
	 * ["IMG"][1]["ALT"] = "xxx"
	 * ["A"][0]["HREF"] = "xxx"
	 *
	*/
	function parseHtml( $s_str ) {
		$i_indicatorL = 0;
		$i_indicatorR = 0;
		$s_tagOption = '';
		$i_arrayCounter = 0;
		$a_html = array();
		// Search for a tag in string
		while( is_int(($i_indicatorL=strpos($s_str,'<',$i_indicatorR))) ) {
			// Get everything into tag...
			$i_indicatorL++;
			$i_indicatorR = strpos($s_str,'>', $i_indicatorL);
			$s_temp = substr($s_str, $i_indicatorL, ($i_indicatorR-$i_indicatorL) );
			$a_tag = explode( ' ', $s_temp );
			// Here we get the tag's name
			list( ,$s_tagName,, ) = each($a_tag);
			$s_tagName = strtolower($s_tagName);
			// Well, I am not interesting in <br>, </font> or anything else like that...
			// So, this is false for tags without options.
			$b_boolOptions = is_array(($s_tagOption=each($a_tag))) && $s_tagOption[1];
			if( $b_boolOptions ) {
				// Without this, we will mess up the array
				$i_arrayCounter = (int)count($a_html[$s_tagName]);
				// get the tag options, like src="http://". Here, s_tagTokOption is 'src' and s_tagTokValue is '"http://"'
				do {
					$s_tagTokOption = strtolower(strtok($s_tagOption[1], '='));
					if ($s_tagTopOption != '/') {
						$s_tagTokValue = str_replace('"','',trim(strtok('=')));
						$a_html[$s_tagName][$i_arrayCounter][$s_tagTokOption] =	$s_tagTokValue;
					}
					$b_boolOptions = is_array(($s_tagOption=each($a_tag))) && $s_tagOption[1];
				} while( $b_boolOptions );
			}
		}
		return $a_html;
	} 

	function displayWarning($message) {
		$out = '
		<div style="padding:15px 15px 20px 0;">
			<div class="typo3-message message-warning">
   				<div class="message-header">' . $this->lang('displayWarning') . '</div>
  				<div class="message-body">
					' . $message . '
				</div>
			</div>
		</div>';

		return $out;
	}

	function displayError($message) {
		$out = '
		<div style="padding:15px 15px 20px 0;">
			<div class="typo3-message message-error">
   				<div class="message-header">' . $this->lang('displayError') . '</div>
  				<div class="message-body">
					' . $message . '
				</div>
			</div>
		</div>';

		return $out;
	}


	function getButton($func, $lbl = 'DO IT') {

		$params = array('do_update' => 1, 'func' => $func);

		$onClick = "document.location='" . t3lib_div::linkThisScript($params) . "'; return false;";
		$button = '<input type="submit" value="' . $lbl . '" onclick="' . htmlspecialchars($onClick) . '">';

		return $button;
	}


	/**
	 * Checks how many rows are found and returns true if there are any
	 * (this function is called from the extension manager)
	 *
	 * @return	boolean
	 */
	function access() {
		return TRUE;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.ext_update.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_news/class.ext_update.php']);
}
?>