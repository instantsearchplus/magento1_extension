<?php

$installer = $this;
$config = Mage::getModel('autocompleteplus_autosuggest/config');
$row = false;
$installer->startSetup();

$tableExists = false;
$tableName = $installer->getTable('autocompleteplus_autosuggest/config');

if (method_exists($installer->getConnection(), 'isTableExists')) {
    $tableExists = $installer->getConnection()->isTableExists($tableName);
} else {
    $tableExists = $installer->tableExists($tableName);
}
if ($tableExists) {
    $select = $installer->getConnection()->select()
        ->from(array('config' => $tableName));
    $row = $installer->getConnection()->fetchAll($select);

    if (method_exists($installer->getConnection(), 'dropTable')) {
        $installer->getConnection()->dropTable($tableName);
    } else {
        $installer->run("DROP TABLE IF EXISTS {$tableName};");
    }
}

if ($row && isset($row[0]['licensekey']) && isset($row[0]['authkey'])) {
    $config->generateConfig($row[0]['licensekey'], $row[0]['authkey']);
} else {
    $config->generateConfig();
}

Mage::app()->getCacheInstance()->cleanType('config');

Mage::log(__FILE__.' triggered', null, 'autocomplete.log', true);
$installer->endSetup();
