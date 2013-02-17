<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "register4cal".
 *
 * Auto generated 15-02-2013 05:02
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Registrations for calendar events',
	'description' => 'Adds a registration functionality to the CAL extension. The registration form can be displayed with the event single display and the event list of the CAL extension. Lists of registrations for users and organizers can be displayed via plugins. Confirmation and notification emails can be sent. All fields from event, user, location and organizer can be used. Additional fields for the registration can be defined via TypoScript.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.18.1',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Thomas Ernst',
	'author_email' => 'typo3@thernst.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.5.0-4.7.99',
			'cal' => '1.3.3-1.5.2',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:69:{s:9:"ChangeLog";s:4:"4b9e";s:20:"class.ext_update.php";s:4:"3b38";s:21:"ext_conf_template.txt";s:4:"62ac";s:12:"ext_icon.gif";s:4:"ecda";s:17:"ext_localconf.php";s:4:"a854";s:15:"ext_php_api.dat";s:4:"5f4f";s:14:"ext_tables.php";s:4:"d361";s:14:"ext_tables.sql";s:4:"84d1";s:31:"icon_tx_register4cal_fields.gif";s:4:"475a";s:34:"icon_tx_register4cal_fieldsets.gif";s:4:"475a";s:38:"icon_tx_register4cal_registrations.gif";s:4:"0221";s:16:"locallang_db.xml";s:4:"365c";s:17:"locallang_tca.php";s:4:"9b6b";s:20:"locallang_update.xml";s:4:"47d2";s:10:"README.txt";s:4:"ee2d";s:7:"tca.php";s:4:"d69a";s:42:"calview/class.tx_register4cal_activate.php";s:4:"c71b";s:42:"calview/class.tx_register4cal_fieldset.php";s:4:"a72e";s:41:"calview/class.tx_register4cal_listreg.php";s:4:"6235";s:46:"calview/class.tx_register4cal_maxattendees.php";s:4:"6d4d";s:40:"calview/class.tx_register4cal_regend.php";s:4:"5253";s:42:"calview/class.tx_register4cal_regstart.php";s:4:"7b16";s:40:"calview/class.tx_register4cal_submit.php";s:4:"e607";s:42:"calview/class.tx_register4cal_waitlist.php";s:4:"f143";s:42:"classes/class.tx_register4cal_datetime.php";s:4:"c764";s:45:"classes/class.tx_register4cal_tsparserext.php";s:4:"f4cb";s:53:"controller/class.tx_register4cal_admin_controller.php";s:4:"bf82";s:52:"controller/class.tx_register4cal_base_controller.php";s:4:"09be";s:58:"controller/class.tx_register4cal_listoutput_controller.php";s:4:"0dba";s:60:"controller/class.tx_register4cal_listregister_controller.php";s:4:"5a9c";s:62:"controller/class.tx_register4cal_singleregister_controller.php";s:4:"7d85";s:58:"controller/class.tx_register4cal_validation_controller.php";s:4:"a1fc";s:17:"doc/manual-de.pdf";s:4:"7317";s:17:"doc/manual-de.sxw";s:4:"c6c0";s:14:"doc/manual.pdf";s:4:"ea0d";s:14:"doc/manual.sxw";s:4:"3e5c";s:45:"hooks/class.tx_register4cal_backend_hooks.php";s:4:"844b";s:46:"hooks/class.tx_register4cal_frontend_hooks.php";s:4:"2c31";s:50:"model/class.tx_register4cal_registration_model.php";s:4:"6f47";s:40:"model/class.tx_register4cal_settings.php";s:4:"e585";s:33:"pi1/class.tx_register4cal_pi1.php";s:4:"9497";s:19:"pi1/flexform_ds.xml";s:4:"37f1";s:22:"static/basic/setup.txt";s:4:"347c";s:31:"static/fe-editing/constants.txt";s:4:"80ae";s:27:"static/fe-editing/setup.txt";s:4:"0fd5";s:34:"static/old_templates/constants.txt";s:4:"80ae";s:30:"static/old_templates/setup.txt";s:4:"d030";s:27:"templates/flashmessages.css";s:4:"4e2c";s:23:"templates/register.tmpl";s:4:"b519";s:40:"templates/cal_classic/confirm_event.tmpl";s:4:"17ba";s:39:"templates/cal_classic/create_event.tmpl";s:4:"9a6c";s:38:"templates/cal_classic/event_model.tmpl";s:4:"4520";s:31:"templates/cal_classic/list.tmpl";s:4:"a3f7";s:36:"templates/cal_classic/typoscript.txt";s:4:"9a70";s:41:"templates/cal_standard/confirm_event.tmpl";s:4:"8cad";s:40:"templates/cal_standard/create_event.tmpl";s:4:"e72d";s:39:"templates/cal_standard/event_model.tmpl";s:4:"835b";s:32:"templates/cal_standard/list.tmpl";s:4:"fa7a";s:37:"templates/cal_standard/typoscript.txt";s:4:"f359";s:23:"templates/gfx/error.png";s:4:"e4dd";s:29:"templates/gfx/information.png";s:4:"3750";s:24:"templates/gfx/notice.png";s:4:"a882";s:20:"templates/gfx/ok.png";s:4:"8bfe";s:25:"templates/gfx/warning.png";s:4:"c847";s:40:"view/class.tx_register4cal_base_view.php";s:4:"8630";s:46:"view/class.tx_register4cal_listoutput_view.php";s:4:"fa44";s:44:"view/class.tx_register4cal_register_view.php";s:4:"14db";s:52:"view/class.tx_register4cal_userdefinedfield_view.php";s:4:"f3c8";s:18:"view/locallang.xml";s:4:"6b4d";}',
);

?>