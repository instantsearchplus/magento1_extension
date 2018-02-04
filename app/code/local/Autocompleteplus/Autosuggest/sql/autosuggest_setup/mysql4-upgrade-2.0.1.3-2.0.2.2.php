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

DROP TABLE IF EXISTS {$this->getTable('autocompleteplus_batches')};

CREATE TABLE IF NOT EXISTS {$this->getTable('autocompleteplus_batches')} (

  `id` int(11) NOT NULL auto_increment,

   `product_id` INT NULL,

   `store_id` INT NOT NULL,

   `update_date` INT DEFAULT NULL,

   `action` VARCHAR( 255 ) NOT NULL,

   `sku` VARCHAR( 255 ) NOT NULL,

   PRIMARY KEY  (`id`)

) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

");

Mage::log('mysql4-upgrade-2.0.1.3-2.0.2.2.php triggered',null,'autocomplete.log',true);
$installer->endSetup();

?>