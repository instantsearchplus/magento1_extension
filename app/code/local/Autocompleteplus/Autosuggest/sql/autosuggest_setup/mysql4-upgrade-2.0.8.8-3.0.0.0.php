<?php

$installer = $this;
$config = Mage::getModel('autocompleteplus_autosuggest/config');
$row = false;
$installer->startSetup();

if ($installer->getConnection()->isTableExists($installer->getTable('autocompleteplus_autosuggest/config'))) {
    $select = $installer->getConnection()->select()
        ->from(array('config' => $installer->getTable('autocompleteplus_autosuggest/config')));
    $row = $installer->getConnection()->fetchOne($select);
    $installer->getConnection()->dropTable($installer->getTable('autocompleteplus_autosuggest/config'));
}

if ($row) {
    $config->generateConfig($row['licensekey']);
} else {
    $config->generateConfig();
}

Mage::app()->getCacheInstance()->cleanType('config');

Mage::log(__FILE__ . ' triggered', null, 'autocomplete.log', true);
$installer->endSetup();
