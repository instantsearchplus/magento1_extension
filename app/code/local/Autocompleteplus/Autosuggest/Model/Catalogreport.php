<?php

class Autocompleteplus_Autosuggest_Model_Catalogreport extends Mage_Core_Model_Abstract
{
    protected $_storeId;

    public function getDisabledProductsCount()
    {
        try {
            $collection = $this->getProductCollectionStoreFilterFactory();
            $this->addDisabledFilterToCollection($collection);

            return $collection->getSize();
        } catch (Exception $e) {
            return -1;
        }
    }

    public function getEnabledProductsCount()
    {
        try {
            $collection = $this->getProductCollectionStoreFilterFactory();
            $this->addEnabledFilterToCollection($collection);

            return $collection->getSize();
        } catch (Exception $e) {
            return -1;
        }
    }

    public function getSearchableProductsCount()
    {
        try {
            $collection = $this->getProductCollectionStoreFilterFactory();
            $this->addEnabledFilterToCollection($collection);
            $this->addVisibleInSearchFilterToCollection($collection);

            return $collection->getSize();
        } catch (Exception $e) {
            return -1;
        }
    }

    public function getSearchableProducts2Count()
    {
        try {
            $num_of_searchable_products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($this->getCurrentStoreId())
                ->addAttributeToFilter('status', array('eq' => 1))          // Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                ->addAttributeToFilter(array(
                        array('attribute' => 'visibility', 'finset' => 3),  // visibility Search
                        array('attribute' => 'visibility', 'finset' => 4),  // visibility Catalog, Search
                ))
                ->getSize();

            return $num_of_searchable_products;
        } catch (Exception $e) {
            return -1;
        }
    }

    protected function getProductCollectionStoreFilterFactory()
    {
        return Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($this->getCurrentStoreId());
    }

    public function addEnabledFilterToCollection($collection)
    {
        return $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
    }

    public function addDisabledFilterToCollection($collection)
    {
        return $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED));
    }

    public function addVisibleInCatalogFilterToCollection($collection)
    {
        return Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
    }

    public function addVisibleInSearchFilterToCollection($collection)
    {
        return Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
    }

    /**
     * Return the current store - can be overridden with post.
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        if (!$this->_storeId) {
            $post = $this->getRequest()->getParams();
            if (array_key_exists('store_id', $post)) {
                $this->_storeId = $post['store_id'];
            } elseif (array_key_exists('store', $post)) {
                $this->_storeId = $post['store'];
            } else {
                $this->_storeId = Mage::app()->getStore()->getStoreId();
            }
        }

        return $this->_storeId;
    }

    public function getRequest()
    {
        return Mage::app()->getRequest();
    }
}
