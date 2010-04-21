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
		'status' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.status',		
			'config' => array (
				'type' => 'select',
				'size' => 1,
				'minitems' => 1,
				'maxitems' =>1,
				'readOnly' => 1, 
				'items' => Array (
					Array('LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.status.1', 1),
					Array('LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.status.2', 2),
					Array('LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_registrations.status.3', 3),
				)
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
		'0' => array('showitem' => 'cal_event_uid;;;;1-1-1, feuser_uid, status, additional_data')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_register4cal_fields'] = array (
	'ctrl' => $TCA['tx_register4cal_fields']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,name,caption,type,options,width,height,isnumparticipants'
	),
	'feInterface' => $TCA['tx_register4cal_fields']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_register4cal_fields',
				'foreign_table_where' => 'AND tx_register4cal_fields.pid=###CURRENT_PID### AND tx_register4cal_fields.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'name' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.name',		
			'l10n_mode' => 'exclude',
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'caption' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.caption',		
			'l10n_mode' => 'prefixLangTitle',
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'type' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.type',		
			'l10n_mode' => 'exclude',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('','0'),
					array('LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.type.I.1', '1'),
					array('LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.type.I.2', '2'),
					array('LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.type.I.3', '3'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'options' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.options',		
			'l10n_mode' => 'prefixLangTitle',
			'config' => array (
				'type' => 'input',	
				'size' => '100',
				'eval' => 'required',
			)
		),
		'defaultvalue' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.defaultvalue',		
			'l10n_mode' => 'prefixLangTitle',
			'config' => array (
				'type' => 'input',	
				'size' => '100',
			)
		),		
		'width' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.width',		
			'l10n_mode' => 'exclude',
			'config' => array (
				'type' => 'input',	
				'size' => '3',	
				'range' => array ('lower'=>1,'upper'=>200),	
				'eval' => 'int',
			)
		),
		'height' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.height',		
			'l10n_mode' => 'exclude',
			'config' => array (
				'type' => 'input',	
				'size' => '3',	
				'range' => array ('lower'=>1, 'upper'=>20),	
				'eval' => 'int',
			)
		),
		'isnumparticipants' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fields.isnumparticipants',		
			'l10n_mode' => 'exclude',
			'config' => array (
				'type' => 'check',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, type'),
		'1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, name, caption, type, defaultvalue, width, isnumparticipants'),
		'2' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, name, caption, type, defaultvalue, width, height'),
		'3' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, name, caption, type, options, defaultvalue, width, height'),
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_register4cal_fieldsets'] = array (
	'ctrl' => $TCA['tx_register4cal_fieldsets']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'name,fields,isdefault'
	),
	'feInterface' => $TCA['tx_register4cal_fieldsets']['feInterface'],
	'columns' => array (
		'name' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fieldsets.name',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'fields' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fieldsets.fields',		
			'config' => array (
				'type' => 'select',	
				'foreign_table' => 'tx_register4cal_fields',	
				'foreign_table_where' => 'AND tx_register4cal_fields.sys_language_uid in (0,-1) AND tx_register4cal_fields.pid=###CURRENT_PID### ORDER BY tx_register4cal_fields.name',	
				'size' => 10,	
				'minitems' => 1,
				'maxitems' => 20,
			)
		),
		'isdefault' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:register4cal/locallang_db.xml:tx_register4cal_fieldsets.isdefault',		
			'config' => array (
				'type' => 'check',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'name;;;;1-1-1, fields, isdefault')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>