<?php

class Autocompleteplus_Autosuggest_Model_Service
{
    public function populatePusher()
    {
        $inserts = array();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $multistoreJson = $helper->getMultiStoreDataJson();
        $storesInfo = json_decode($multistoreJson);

        //truncate the log table
        Mage::getResourceModel('autocompleteplus_autosuggest/pusher')->truncate();

        foreach ($storesInfo->stores as $i => $store) {
            $id = $store->store_id;

            $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStoreId($id);
            
            $productsCount = $productCollection->getSize();

            $batches = ceil($productsCount / 100);
            $offset = 0;

            for ($j = 1; $j <= $batches; ++$j) {
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
            $write = $this->_getWriteAdapter();
            $tableName = $this->_getTable('autocompleteplus_autosuggest/pusher');
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
