<?php

$installer = $this;
$tableName = $this->getTable('autocompleteplus_batches');
$columnName = 'update_date';
$checkColumnType = false;
$fileIo = new Varien_Io_File();
$baseDir = Mage::getBaseDir();
$fileIo->open(array('path' => $baseDir));

/*
 * Check if getEdition method does not exist in Mage
 * Check if Enterprise Edition license exists in Magento root
 * Check if getVersion method exists in Mage
 * Compare versions to check if less than 1.10.0.0
 * Required to use file_exists because Varien_Io_File isValid missing < 1.8CE
 */
// @codingStandardsIgnoreLine
if (!method_exists('Mage', 'getEdition') && file_exists($baseDir.DS.'LICENSE_EE.txt') && method_exists('Mage', 'getVersion') && version_compare(Mage::getVersion(), '1.10.0.0.', '<') === true) {
    $tableExists = $installer->getConnection()->showTableStatus($tableName);
} else {
    if (method_exists($installer->getConnection(), 'isTableExists')) {
        $tableExists = $installer->getConnection()->isTableExists($tableName);
    } else {
        $tableExists = $installer->tableExists($tableName);
    }
}

if ($tableExists) {
    try {
        $checkIfExists = $installer->getConnection()->tableColumnExists($tableName, 'update_date');

        /*
         * Check if table already exists
         */
        if (!$checkIfExists) {

            /*
             * Check if column 'update_date' is type INT
             */
            $describe = $this->getConnection()->describeTable($tableName);
            foreach ($describe as $column) {
                if ($column['COLUMN_NAME'] == $columnName && $column['DATA_TYPE'] == Varien_Db_Ddl_Table::TYPE_INTEGER) {
                    $checkColumnType = true;
                }
            }

            if (!$checkColumnType) {
                $installer->startSetup();

                if (method_exists($installer->getConnection(), 'dropTable')) {
                    $installer->getConnection()->dropTable($tableName);
                } else {
                    $installer->run("DROP TABLE IF EXISTS {$tableName};");
                }

                $installer->getConnection()->newTable($tableName)
                    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                        'comment' => 'Batch ID',
                    ))
                    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                        'nullable' => false,
                        'comment' => 'Product ID',
                    ))
                    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                        'nullable' => false,
                        'comment' => 'Store ID',
                    ))
                    ->addColumn('update_date', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                        'nullable' => true,
                        'comment' => 'Update Time Integer',
                    ))
                    ->addColumn('action', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                        'nullable' => false,
                        'comment' => 'Batch Action',
                    ))
                    ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                        'nullable' => false,
                        'comment' => 'Product SKU',
                    ));
                $installer->endSetup();
            }
        }
    } catch (Exception $e) {
        $errMsg = $e->getMessage();
        Mage::log('Install failed with a message: '.$errMsg, null, 'autocomplete.log', true);

        $command = 'http://magento.instantsearchplus.com/install_error';
        $helper = Mage::helper('autocompleteplus_autosuggest');
        //getting site url
        $url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

        $data = array();
        $data['site'] = $url;
        $data['msg'] = $errMsg;
        $data['f'] = '2.0.7.3';
        $res = $helper->sendPostCurl($command, $data);
    }

    Mage::log(__FILE__.' triggered', null, 'autocomplete.log', true);
}
