<?php

$installer = $this;
$config = Mage::getModel('autocompleteplus_autosuggest/config');
$row = false;
$installer->startSetup();

if ($installer->getConnection()->isTableExists($installer->getTable('autocompleteplus_autosuggest/config'))) {
    $select = $installer->getConnection()->select()
        ->from(array('config' => $installer->getTable('autocompleteplus_autosuggest/config')));
    $row = $installer->getConnection()->fetchAll($select);
    $installer->getConnection()->dropTable($installer->getTable('autocompleteplus_autosuggest/config'));
}

if ($row && isset($row[0]['licensekey']) && isset($row[0]['authkey'])) {
    $config->generateConfig($row[0]['licensekey'], $row[0]['authkey']);
} else {
    $config->generateConfig();
}

Mage::app()->getCacheInstance()->cleanType('config');

Mage::log(__FILE__ . ' triggered', null, 'autocomplete.log', true);
$installer->endSetup();
