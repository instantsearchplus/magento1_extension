<?php

class Autocompleteplus_Autosuggest_Model_Renderer_Abstract extends Mage_Core_Model_Abstract
{
    protected $_currency;
    protected $_pageNum;
    protected $_productCollection;
    protected $_xmlGenerator;
    protected $_imageField;
    protected $_storeId;
    protected $_monthInterval;
    protected $_helper;

    public function getStoreId()
    {
        if (!$this->_storeId) {
            $this->_storeId = Mage::app()->getStore()->getId();
        }

        return $this->_storeId;
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;

        return $this;
    }

    public function getCurrency()
    {
        if (!$this->_currency) {
            $this->_currency = Mage::app()->getStore($this->getStoreId())->getCurrentCurrencyCode();
        }

        return $this->_currency;
    }

    public function getMonthInterval()
    {
        if (!$this->_monthInterval) {
            $this->_monthInterval = 12;
        }

        return $this->_monthInterval;
    }

    public function setMonthInterval($monthInterval)
    {
        $this->_monthInterval = $monthInterval;

        return $this;
    }

    public function getPageNum()
    {
        if ($this->_pageNum) {
            $this->_pageNum = 1;
        }

        return $this->_pageNum;
    }

    public function setPageNum($pageNum)
    {
        $this->_pageNum = $pageNum;

        return $this;
    }

    public function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('autocompleteplus_autosuggest');
        }

        return $this->_helper;
    }

    public function getProductCollection($new = false)
    {
        if (!$this->_productCollection) {
            $this->_productCollection = Mage::getModel('catalog/product')->getCollection();
        }

        if ($new === true) {
            return Mage::getModel('catalog/product')->getCollection();
        }

        return $this->_productCollection;
    }
}
