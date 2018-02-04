<?php
$installer = $this;
$installer->startSetup();

// $installer->run("ALTER TABLE {$this->getTable('sales/quote_item')} ADD `added_from_search` TINYINT(1) NULL  DEFAULT NULL COMMENT 'AutocompletePlus Add Flag';");

$installer->endSetup();
