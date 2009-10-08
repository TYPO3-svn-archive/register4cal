<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_register4cal_pi1.php', '_pi1', 'list_type', 0);
require_once(t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_user1.php');

if (TYPO3_MODE == 'FE') {
	//require rlmp_dateselectlib-class if this extension is installed
	if (t3lib_extMgm::isLoaded('rlmp_dateselectlib')) {
		require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');
	}

	//Use hooks in cal extension to display the registration form and save registrations stored in list view
	require_once(t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_fehooks.php');
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/controller/class.tx_cal_controller.php']['finishViewRendering'][]='EXT:register4cal/classes/class.tx_register4cal_fehooks.php:tx_register4cal_fehooks';
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/controller/class.tx_cal_controller.php']['drawlistClass'][]='EXT:register4cal/classes/class.tx_register4cal_fehooks.php:tx_register4cal_fehooks';
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/model/class.tx_cal_base_model.php']['searchForObjectMarker'][]='EXT:register4cal/classes/class.tx_register4cal_fehooks.php:tx_register4cal_fehooks';

	//Define services, used by the cal extension when displaying the additional fields in frontend editing
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_activate',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_services.php',
			'className' => 'tx_register4cal_activate',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_regstart',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_services.php',
			'className' => 'tx_register4cal_regstart',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_regend',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_services.php',
			'className' => 'tx_register4cal_regend',
		)
	);
	
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_listreg',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_services.php',
			'className' => 'tx_register4cal_listreg',
		)
	);	

	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_submit',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_services.php',
			'className' => 'tx_register4cal_submit',
		)
	);

} else {
	//Use hook to display additional data in the backend
	require_once(t3lib_extMgm::extPath($_EXTKEY).'classes/class.tx_register4cal_behooks.php');
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:register4cal/classes/class.tx_register4cal_behooks.php:tx_register4cal_behooks';
}
//ThEr080409: End of changes ---------------------------------------------------------------------------------------------------------------
?>