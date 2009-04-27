<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_register4cal_registrations'] = array (
	'ctrl' => $TCA['tx_register4cal_registrations']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'cal_event_uid,feuser_uid,additional_data'
	),
	'feInterface' => $TCA['tx_register4cal_registrations']['feInterface'],
	'columns' => array (
		'recordlabel' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.recordlabel',		
			'config' => array (
				'type' => 'none',
			)
		),
		'cal_event_uid' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.cal_event_uid',		
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_cal_event',
				'size' => 1,
				'minitems' => 1,
				'maxitems' =>1,
				'readOnly' => 1, 
			)
		),
		'feuser_uid' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.feuser_uid',		
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => 1,
				'minitems' => 1,
				'maxitems' =>1,
				'readOnly' => 1, 
			)
		),
		'additional_data' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.additional_data',		
			'config' => array (
				'type' => 'user',
				'userFunc' => 'tx_register4cal_user1->additionalDataForBackend',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'cal_event_uid;;;;1-1-1, feuser_uid, additional_data')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>