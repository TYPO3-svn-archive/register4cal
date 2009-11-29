<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_register4cal_registrations'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations',		
		'label'     => 'recordlabel',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'delete' => 'deleted',	
		'readOnly' => 0, 
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_register4cal_registrations.gif',
	),
);

$tempColumns = array (
	'tx_register4cal_activate' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_activate',		
		'config' => array (
			'type' => 'check',
		)
	),
	'tx_register4cal_regstart' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_regstart',		
		'config' => array (
			'type'     => 'input',
			'size'     => '8',
			'max'      => '20',
			'eval'     => 'date',
			'checkbox' => '0',
			'default'  => '0'
		)
	),
	'tx_register4cal_regend' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_regend',		
		'config' => array (
			'type'     => 'input',
			'size'     => '8',
			'max'      => '20',
			'eval'     => 'date',
			'checkbox' => '0',
			'default'  => '0'
		)
	),
	'tx_register4cal_maxattendees' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_maxattendees',		
		'config' => array (
			'type'     => 'input',
			'size'     => '8',
			'max'      => '10',
			'eval'     => 'num',
			'checkbox' => '0',
			'default'  => '0'
		)
	),

	'tx_register4cal_waitlist' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_waitlist',		
		'config' => array (
			'type' => 'check',
		)
	),
);

t3lib_div::loadTCA('tx_cal_event');
t3lib_extMgm::addTCAcolumns('tx_cal_event',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tx_cal_event','--div--;LLL:EXT:register4cal/locallang_db.xml:tx_cal_event.tx_register4cal_tablabel,tx_register4cal_activate;;;;1-1-1, tx_register4cal_regstart, tx_register4cal_regend, tx_register4cal_maxattendees, tx_register4cal_waitlist');

$tempColumns = Array (
	'tx_register4cal_feUserId' => array (		
		'exclude' => 1,		
		'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_cal_organizer.tx_register4cal_feUserId',		
		'config' => Array (
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => 'fe_users',	
			'size' => 5,	
			'minitems' => 0,
			'maxitems' => 5,
		)
	),
);

t3lib_div::loadTCA('tx_cal_organizer');
t3lib_extMgm::addTCAcolumns('tx_cal_organizer',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tx_cal_organizer','tx_register4cal_feUserId;;;;1-1-1');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1','FILE:EXT:'.$_EXTKEY.'/pi1/flexform_ds.xml');

t3lib_extMgm::addPlugin(array('LLL:EXT:register4cal/locallang_db.xml:tt_content.list_type_pi1',$_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/registrations4cal/', 'registrations4cal');
?>