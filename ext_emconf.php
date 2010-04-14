<?php

########################################################################
# Extension Manager/Repository config file for ext "register4cal".
#
# Auto generated 14-04-2010 14:15
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
	'version' => '0.5.2',
	'constraints' => array(
		'depends' => array(
			'cal' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:34:{s:9:"ChangeLog";s:4:"2d69";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"ecda";s:17:"ext_localconf.php";s:4:"ea6b";s:15:"ext_php_api.dat";s:4:"5f4f";s:14:"ext_tables.php";s:4:"856a";s:14:"ext_tables.sql";s:4:"0f65";s:38:"icon_tx_register4cal_registrations.gif";s:4:"0221";s:16:"locallang_db.xml";s:4:"9b7a";s:17:"locallang_tca.php";s:4:"9b6b";s:7:"tca.php";s:4:"327b";s:42:"classes/class.tx_register4cal_activate.php";s:4:"f446";s:41:"classes/class.tx_register4cal_behooks.php";s:4:"b9af";s:40:"classes/class.tx_register4cal_checks.php";s:4:"3fd1";s:41:"classes/class.tx_register4cal_fehooks.php";s:4:"ba2e";s:41:"classes/class.tx_register4cal_listreg.php";s:4:"f1ba";s:38:"classes/class.tx_register4cal_main.php";s:4:"9d86";s:46:"classes/class.tx_register4cal_maxattendees.php";s:4:"ef4d";s:40:"classes/class.tx_register4cal_regend.php";s:4:"2eb2";s:42:"classes/class.tx_register4cal_regstart.php";s:4:"65ef";s:40:"classes/class.tx_register4cal_render.php";s:4:"2d17";s:40:"classes/class.tx_register4cal_submit.php";s:4:"0225";s:39:"classes/class.tx_register4cal_user1.php";s:4:"b96a";s:42:"classes/class.tx_register4cal_waitlist.php";s:4:"03ac";s:21:"classes/locallang.xml";s:4:"79c4";s:14:"doc/manual.pdf";s:4:"7403";s:14:"doc/manual.sxw";s:4:"c89e";s:17:"doc/manual_de.pdf";s:4:"aed5";s:17:"doc/manual_de.sxw";s:4:"d873";s:33:"pi1/class.tx_register4cal_pi1.php";s:4:"37e2";s:19:"pi1/flexform_ds.xml";s:4:"37f1";s:38:"static/registrations4cal/constants.txt";s:4:"80ae";s:34:"static/registrations4cal/setup.txt";s:4:"ed73";s:23:"templates/register.tmpl";s:4:"a3b9";}',
	'suggests' => array(
	),
);

?>