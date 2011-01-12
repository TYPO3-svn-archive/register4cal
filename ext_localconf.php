<?php
// TODO SEV3 Rewrite manuals
// TODO SEV3 Remove update task
// TODO SEV9 Version 0.7.1 Add backend module
// TODO SEV9 Version 0.7.1 Add scheduler task to delete past registrations
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_register4cal_pi1.php', '_pi1', 'list_type', 0);
require_once(t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_datetime.php');

if (TYPO3_MODE == 'FE') {
	//require rlmp_dateselectlib-class if this extension is installed
	if (t3lib_extMgm::isLoaded('rlmp_dateselectlib')) {
		require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');
	}

	//Use hooks in cal extension to display the registration form and save registrations stored in list view
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/controller/class.tx_cal_controller.php']['finishViewRendering'][]='EXT:register4cal/hooks/class.tx_register4cal_frontend_hooks.php:tx_register4cal_frontend_hooks';
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/controller/class.tx_cal_controller.php']['drawlistClass'][]='EXT:register4cal/hooks/class.tx_register4cal_frontend_hooks.php:tx_register4cal_frontend_hooks';
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/model/class.tx_cal_base_model.php']['searchForObjectMarker'][]='EXT:register4cal/hooks/class.tx_register4cal_frontend_hooks.php:tx_register4cal_frontend_hooks';

	

	//Define services, used by the cal extension when displaying the additional fields in frontend editing
	require_once(t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_activate.php');
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_activate',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_activate.php',
			'className' => 'tx_register4cal_activate',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_regstart',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_regstart.php',
			'className' => 'tx_register4cal_regstart',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_regend',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_regend.php',
			'className' => 'tx_register4cal_regend',
		)
	);
	require_once(t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_maxattendees.php');
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_maxattendees',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_maxattendees.php',
			'className' => 'tx_register4cal_maxattendees',
		)
	);
	require_once(t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_waitlist.php');
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_waitlist',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_waitlist.php',
			'className' => 'tx_register4cal_waitlist',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_fieldset',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_fieldset.php',
			'className' => 'tx_register4cal_fieldset',
		)
	);	
	
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_listreg',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_listreg.php',
			'className' => 'tx_register4cal_listreg',
		)
	);	

	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_submit',
		array(
			'title' => 'Extension Model for tx_register4cal fields', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'calview/class.tx_register4cal_submit.php',
			'className' => 'tx_register4cal_submit',
		)
	);

} else {
	//Use hook to display additional data in the backend
	require_once(t3lib_extMgm::extPath($_EXTKEY).'hooks/class.tx_register4cal_backend_hooks.php');
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:register4cal/hooks/class.tx_register4cal_backend_hooks.php:tx_register4cal_backend_hooks';
}
?>