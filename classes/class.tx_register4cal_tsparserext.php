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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class that renders fields for the extensionmanager configuration
 * (taken from tt_news and adapted)
 *
 *
 * @author  Thomas Ernst <typo3@thernst.de>
 * @package TYPO3
 * @subpackage register4cal
 */
class tx_register4cal_tsparserext {


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function displayMessage(&$params, &$tsObj) {
		$out = '';

		if (t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
				// 4.3.0 comes with flashmessages styles. For older versions we include the needed styles here
			$cssPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('register4cal');
			$out .= '<link rel="stylesheet" type="text/css" href="' . $cssPath . 'templates/flashmessages.css" media="screen" />';
		}

		$out .= '
		<div style="position:absolute;top:10px;right:10px; width:300px;">
			<div class="typo3-message message-information">
   				<div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:register4cal/locallang_update.xml:infobox.header') . '</div>
  				<div class="message-body">
  					' . $GLOBALS['LANG']->sL('LLL:EXT:register4cal/locallang_update.xml:infobox.message') . '<br />
  					<a style="text-decoration:underline;" href="index.php?&amp;id=0&amp;CMD[showExt]=register4cal&amp;SET[singleDetails]=updateModule">
  					' . $GLOBALS['LANG']->sL('LLL:EXT:register4cal/locallang_update.xml:infobox.link') . '</a>
  				</div>
  			</div>
  		</div>
  		';

		return $out;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_ttnews_tsparserext.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/register4cal/classes/class.tx_ttnews_tsparserext.php']);
}
?>