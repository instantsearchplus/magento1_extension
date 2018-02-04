<?php
class Autocompleteplus_Autosuggest_Model_Api extends Mage_Api_Model_Resource_Abstract
{
    public function setLayeredSearchOn($scope, $scope_id) {
        $core_config = new Mage_Core_Model_Config();
        try {
            $core_config->saveConfig('autocompleteplus/config/layered', "1", $scope, $scope_id);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return "Done";
    }

    public function setLayeredSearchOff($scope, $scope_id) {
        $core_config = new Mage_Core_Model_Config();
        try {
            $core_config->saveConfig('autocompleteplus/config/layered', "0", $scope, $scope_id);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return "Done";
    }

    public function getLayeredSearchConfig($scope_id) {
        try {
            Mage::app()->getCacheInstance()->cleanType('config');
            $layered = Mage::getStoreConfig('autocompleteplus/config/layered', $scope_id);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $layered;
    }
}