<?php
/**
 * InstantSearchPlus (Autosuggest)

 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 * @package    InstantSearchPlus
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS `{$this->getTable('autocompleteplus_autosuggest/notifications')}`;
CREATE TABLE IF NOT EXISTS `{$installer->getTable('autocompleteplus_autosuggest/notifications')}` (
	`notification_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `type` varchar(32) default NULL,
    `subject` varchar(255) default NULL,
	`message` text,
    `timestamp` varchar(32) default NULL,
    `is_active` tinyint(1) NOT NULL default '1',
	PRIMARY KEY (`notification_id`),
	KEY `IDX_TYPE` (`type`),
	KEY `IDX_IS_ACTIVE` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
Mage::log('mysql4-upgrade-2.0.2.5-2.0.2.6.php',null,'autocomplete.log',true);
$installer->endSetup();