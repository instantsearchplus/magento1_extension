<?php

$installer = $this;
$installer->startSetup();

// config cleanup - we need the updated values
Mage::app()->getStore()->resetConfig();

$config = Mage::getModel('autocompleteplus_autosuggest/config');

if (!$config->isConfigDataValid()) {
    $config->generateConfig();
}

Mage::app()->getCacheInstance()->cleanType('config');

Mage::log(__FILE__.' triggered', null, 'autocomplete.log', true);
$installer->endSetup();
