<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_register4cal_pi1.php', '_pi1', 'list_type', 0);
require_once(t3lib_extMgm::extPath($_EXTKEY).'user/class.tx_register4cal_user1.php');

if (TYPO3_MODE == 'FE') {
	require_once(t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_register4cal_hook1.php');
	$GLOBALS['TYPO3_CONF_VARS']['FE']['EXTCONF']['ext/cal/controller/class.tx_cal_controller.php']['finishViewRendering'][]='EXT:register4cal/pi1/class.tx_register4cal_hook1.php:tx_register4cal_hook1';

	//ThEr100409: Start of changes -----------------------------------------------------------------------------------------------------
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_activate',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'model/class.tx_register4cal_fields.php',
			'className' => 'tx_register4cal_activate',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_regstart',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'model/class.tx_register4cal_fields.php',
			'className' => 'tx_register4cal_regstart',
		)
	);
	t3lib_extMgm::addService($_EXTKEY,  'register4cal',  'tx_register4cal_regend',
		array(
			'title' => 'Extension Model for tx_register4cal fields"', 'description' => '', 'subtype' => 'module',
			'available' => TRUE, 'priority' => 50, 'quality' => 50,
			'os' => '', 'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'model/class.tx_register4cal_fields.php',
			'className' => 'tx_register4cal_regend',
		)
	);
	//ThEr100409: End of changes -------------------------------------------------------------------------------------------------------
} else {
//ThEr080409: Start of changes -------------------------------------------------------------------------------------------------------------
	require_once(t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_register4cal_hook2.php');
	$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:register4cal/pi1/class.tx_register4cal_hook2.php:tx_register4cal_hook2';
}
//ThEr080409: End of changes ---------------------------------------------------------------------------------------------------------------
?>