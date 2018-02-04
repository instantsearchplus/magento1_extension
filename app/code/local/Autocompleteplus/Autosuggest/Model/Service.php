<?php

class Autocompleteplus_Autosuggest_Model_Service
{
    public function populatePusher()
    {
        $inserts = array();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $multistoreJson = $helper->getMultiStoreDataJson();
        $storesInfo = json_decode($multistoreJson);
        $stores = is_array($storesInfo->stores) ? $stores : array($stores);

        $productCollection = Mage::getModel('catalog/product')->getCollection()->setStoreId($id);
        $productsCount = $productCollection->getSize();
        $write = $this->_getWriteAdapter();
        $tableName = $this->_getTable('autocompleteplus_autosuggest/pusher');

        //truncate the log table
        Mage::getResourceModel('autocompleteplus_autosuggest/pusher')->truncate();

        foreach ($storesInfo->stores as $i => $store) {
            $id = $store->store_id;
            $batches = ceil($productsCount / 100);
            $offset = 0;

            for ($j = 1;$j <= $batches;++$j) {
                $inserts[] = array(
                    'store_id' => $id,
                    'to_send' => $productsCount,
                    'offset' => $offset,
                    'batch_number' => $j,
                    'total_batches' => $batches,
                    'sent' => 0,
                );

                $offset += 100;
            }
        }

        if ($inserts) {
            $write->insertMultiple($tableName, $inserts);
        }
    }

    protected function _getWriteAdapter()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    protected function _getTable($resourceName)
    {
        return Mage::getSingleton('core/resource')->getTableName($resourceName);
    }
}
