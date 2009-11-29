#
# Table structure for table 'tx_register4cal_registrations'
#
CREATE TABLE tx_register4cal_registrations (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	recordlabel tinytext,
	cal_event_uid int(11) unsigned DEFAULT '0' NOT NULL,
	cal_event_getdate int(11) DEFAULT '0' NOT NULL,
	feuser_uid tinytext,
	additional_data text,
	status tinyint(3) DEFAULT '1' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_cal_event'
#
CREATE TABLE tx_cal_event (
	tx_register4cal_activate tinyint(3) DEFAULT '0' NOT NULL,
	tx_register4cal_regstart int(11) DEFAULT '0' NOT NULL,
	tx_register4cal_regend int(11) DEFAULT '0' NOT NULL,
	tx_register4cal_maxattendees int(11) DEFAULT '0' NOT NULL,
	tx_register4cal_waitlist tinyint(3) DEFAULT '0' NOT NULL,
);

#
# Table structure for table 'tx_cal_organizer'
#
CREATE TABLE tx_cal_organizer (
	tx_register4cal_feUserId tinytext
);
