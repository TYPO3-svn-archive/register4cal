<?php

########################################################################
# Extension Manager/Repository config file for ext "register4cal".
#
# Auto generated 19-06-2010 08:49
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Registrations for calender events',
	'description' => 'Adds a registration functionality to the CAL extension. The registration form can be displayed with the event single display and the event list of the CAL extension. Lists of registrations for users and organizers can be displayed via plugins. Confirmation and notification emails can be sent. All fields from event, user, location and organizer can be used. Additional fields for the registration can be defined via TypoScript.',
	'category' => 'plugin',
	'author' => 'Thomas Ernst',
	'author_email' => 'typo3@thernst.de',
	'shy' => '',
	'dependencies' => 'cal',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.6.1',
	'constraints' => array(
		'depends' => array(
			'cal' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:47:{s:9:"ChangeLog";s:4:"0ee1";s:10:"README.txt";s:4:"ee2d";s:20:"class.ext_update.php";s:4:"42a1";s:21:"ext_conf_template.txt";s:4:"8e06";s:12:"ext_icon.gif";s:4:"ecda";s:17:"ext_localconf.php";s:4:"7c88";s:15:"ext_php_api.dat";s:4:"5f4f";s:14:"ext_tables.php";s:4:"0bb6";s:14:"ext_tables.sql";s:4:"d6b7";s:31:"icon_tx_register4cal_fields.gif";s:4:"475a";s:34:"icon_tx_register4cal_fieldsets.gif";s:4:"475a";s:38:"icon_tx_register4cal_registrations.gif";s:4:"0221";s:16:"locallang_db.xml";s:4:"f16f";s:17:"locallang_tca.php";s:4:"9b6b";s:20:"locallang_update.xml";s:4:"47d2";s:7:"tca.php";s:4:"dcda";s:42:"classes/class.tx_register4cal_activate.php";s:4:"f446";s:41:"classes/class.tx_register4cal_behooks.php";s:4:"b9af";s:40:"classes/class.tx_register4cal_checks.php";s:4:"3fd1";s:41:"classes/class.tx_register4cal_fehooks.php";s:4:"9cc4";s:42:"classes/class.tx_register4cal_fieldset.php";s:4:"2007";s:41:"classes/class.tx_register4cal_listreg.php";s:4:"f1ba";s:38:"classes/class.tx_register4cal_main.php";s:4:"1a15";s:46:"classes/class.tx_register4cal_maxattendees.php";s:4:"ef4d";s:40:"classes/class.tx_register4cal_regend.php";s:4:"2eb2";s:42:"classes/class.tx_register4cal_regstart.php";s:4:"65ef";s:40:"classes/class.tx_register4cal_render.php";s:4:"2fe5";s:40:"classes/class.tx_register4cal_submit.php";s:4:"0225";s:45:"classes/class.tx_register4cal_tsparserext.php";s:4:"8b42";s:39:"classes/class.tx_register4cal_user1.php";s:4:"16bc";s:42:"classes/class.tx_register4cal_waitlist.php";s:4:"03ac";s:21:"classes/locallang.xml";s:4:"cfc8";s:14:"doc/manual.pdf";s:4:"eee9";s:14:"doc/manual.sxw";s:4:"f563";s:17:"doc/manual_de.pdf";s:4:"c9bd";s:17:"doc/manual_de.sxw";s:4:"8d0e";s:33:"pi1/class.tx_register4cal_pi1.php";s:4:"37e2";s:19:"pi1/flexform_ds.xml";s:4:"37f1";s:38:"static/registrations4cal/constants.txt";s:4:"80ae";s:34:"static/registrations4cal/setup.txt";s:4:"45c9";s:27:"templates/flashmessages.css";s:4:"4e2c";s:23:"templates/register.tmpl";s:4:"c576";s:23:"templates/gfx/error.png";s:4:"e4dd";s:29:"templates/gfx/information.png";s:4:"3750";s:24:"templates/gfx/notice.png";s:4:"a882";s:20:"templates/gfx/ok.png";s:4:"8bfe";s:25:"templates/gfx/warning.png";s:4:"c847";}',
	'suggests' => array(
	),
);

?>