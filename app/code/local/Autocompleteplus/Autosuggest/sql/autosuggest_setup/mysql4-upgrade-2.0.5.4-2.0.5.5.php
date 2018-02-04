<?php
$installer = $this;

$installer->startSetup();

// getEdition exist from version 1.12, LICENSE_EE.txt file only exists in EE edition, we need the condition to work on EE version less then 1.11.x.x
if (!method_exists('Mage' , 'getEdition') && file_exists('LICENSE_EE.txt') && method_exists('Mage' , 'getVersion') && version_compare(Mage::getVersion(), '1.10.0.0.', '<') === true){
    $res=$installer->run("
    
    DROP TABLE IF EXISTS {$this->getTable('autocompleteplus_pusher')};
    
    CREATE TABLE IF NOT EXISTS {$this->getTable('autocompleteplus_pusher')} (
    
            `id` INT UNSIGNED NOT NULL auto_increment,
            
            `store_id` INT UNSIGNED NOT NULL,
            
            `to_send` INT UNSIGNED NOT NULL,
            
            `offset` INT UNSIGNED NOT NULL,
            
            `total_batches` INT UNSIGNED NOT NULL,
            
            `batch_number` INT UNSIGNED NOT NULL,
            
            `sent` INT UNSIGNED NOT NULL,
    
            PRIMARY KEY  (`id`)
    
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    
    ");
} else {
    $table = $installer->getConnection()->newTable($installer->getTable('autocompleteplus_autosuggest/pusher'))
    
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'identity' => true,
    
            'unsigned' => true,
    
            'nullable' => false,
    
            'primary' => true
    
        ), 'Id')
    
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'nullable' => false,
    
            'unsigned' => true
    
        ))
    
        ->addColumn('to_send', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'nullable' => false,
    
            'unsigned' => true
    
        ), 'Amount left to send')
    
        ->addColumn('offset', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'nullable' => false,
    
            'unsigned' => true
    
        ))
    
        ->addColumn('total_batches', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'nullable' => false,
    
            'unsigned' => true
    
        ))
    
        ->addColumn('batch_number', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'nullable' => false,
    
            'unsigned' => true
    
        ))
    
        ->addColumn('sent', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    
            'nullable' => false,
    
            'unsigned' => true
    
        ));
    
    /*  TODO:
     * src: http://inchoo.net/magento/delete-test-orders-in-magento/
     * src: http://magento.stackexchange.com/questions/6752/any-way-to-add-block-on-only-enterprise-editions
     * if ($executionPath == 'old') {
            $isTableExists = $connection->showTableStatus($table);
        } else {
            $isTableExists = $connection->isTableExists($table);
        }
     */
    
    if ($installer->getConnection()->isTableExists($table->getName())) {
    
        $installer->getConnection()->dropTable($table->getName());
    
    }
    
    
    
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();

