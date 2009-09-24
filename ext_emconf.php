<?php

########################################################################
# Extension Manager/Repository config file for ext: "register4cal"
#
# Auto generated 21-09-2009 11:55
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'version' => '0.4.0',
	'constraints' => array(
		'depends' => array(
			'cal' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:25:{s:9:"ChangeLog";s:4:"206b";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"ecda";s:17:"ext_localconf.php";s:4:"d90d";s:14:"ext_tables.php";s:4:"3def";s:14:"ext_tables.sql";s:4:"fe21";s:38:"icon_tx_register4cal_registrations.gif";s:4:"0221";s:16:"locallang_db.xml";s:4:"6b39";s:17:"locallang_tca.php";s:4:"9b6b";s:7:"tca.php";s:4:"5719";s:41:"classes/class.tx_register4cal_behooks.php";s:4:"92f6";s:41:"classes/class.tx_register4cal_fehooks.php";s:4:"502d";s:38:"classes/class.tx_register4cal_main.php";s:4:"c75f";s:40:"classes/class.tx_register4cal_render.php";s:4:"d5db";s:42:"classes/class.tx_register4cal_services.php";s:4:"afe0";s:39:"classes/class.tx_register4cal_user1.php";s:4:"ec5e";s:21:"classes/locallang.xml";s:4:"d640";s:23:"templates/register.tmpl";s:4:"49d5";s:33:"pi1/class.tx_register4cal_pi1.php";s:4:"c33e";s:19:"pi1/flexform_ds.xml";s:4:"37f1";s:17:"pi1/locallang.xml";s:4:"fcae";s:38:"static/registrations4cal/constants.txt";s:4:"80ae";s:34:"static/registrations4cal/setup.txt";s:4:"6d32";s:14:"doc/manual.sxw";s:4:"47e8";s:17:"doc/manual_de.sxw";s:4:"459a";}',
	'suggests' => array(
	),
);

?>