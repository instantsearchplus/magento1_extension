<?php

class Autocompleteplus_Autosuggest_Model_Api extends Mage_Api_Model_Resource_Abstract
{
    protected function _getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    public function setLayeredSearchOn($scope, $scopeId)
    {
        try {
            $this->_getConfig()->enableLayeredNavigation($scope, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            Mage::logException($e);

            return $e->getMessage();
        }

        return 'Done';
    }

    public function setLayeredSearchOff($scope, $scopeId)
    {
        try {
            $this->_getConfig()->disableLayeredNavigation($scope, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            Mage::logException($e);

            return $e->getMessage();
        }

        return 'Done';
    }

    public function getLayeredSearchConfig($scopeId)
    {
        try {
            $layered = $this->_getConfig()->getLayeredNavigationStatus($scopeId);
        } catch (Exception $e) {
            Mage::logException($e);

            return $e->getMessage();
        }

        return $layered;
    }
}
