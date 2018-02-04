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
$installer = $this;

$installer->startSetup();

$res=$installer->run("

DROP TABLE IF EXISTS {$this->getTable('autocompleteplus_checksum')};

CREATE TABLE IF NOT EXISTS {$this->getTable('autocompleteplus_checksum')} (

    `identifier` VARCHAR( 255 ) NOT NULL,
    
    `sku` VARCHAR( 255 ) NOT NULL,
    
    `store_id` INT NOT NULL,
    
    `checksum` VARCHAR( 255 ) NOT NULL,
    
    PRIMARY KEY (`identifier`, `store_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

Mage::log('mysql4-upgrade-2.0.4.6-2.0.4.7.php triggered',null,'autocomplete.log',true);
$installer->endSetup();
