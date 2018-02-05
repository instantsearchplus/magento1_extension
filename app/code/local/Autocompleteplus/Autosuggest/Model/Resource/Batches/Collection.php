<?php

class Autocompleteplus_Autosuggest_Model_Resource_Batches_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/batches');
    }

    protected function _getWriteAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function clear()
    {
        $dbAdapter = $this->_getWriteAdapter();

        Zend_Db_Table::setDefaultAdapter($dbAdapter);

        $batchesTable = new Zend_Db_Table($this->getMainTable());

        $where = $batchesTable->getAdapter()->quoteInto('1 > ?', 0);

        $batchesTable->delete($where);

        return $this;
    }
}
