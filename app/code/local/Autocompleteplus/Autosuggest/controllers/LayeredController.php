<?php
/**
 * Layered Controller
 */
class Autocompleteplus_Autosuggest_LayeredController extends Mage_Core_Controller_Front_Action
{
    public function setLayeredSearchOnAction() {
        $authkey = $this->getRequest()->getParam('authentication_key') ? $this->getRequest()->getParam('authentication_key') : "";
        $uuid = $this->getRequest()->getParam('uuid') ? $this->getRequest()->getParam('uuid') : "";
        if (!$this->valid($uuid, $authkey)) {
            echo json_encode(array('status' => 'error: ' . "Authentication failed"));
            return;
        }

        $scope = $this->getRequest()->getParam('scope') ? $this->getRequest()->getParam('scope') : 'stores';
        $store_id = $this->getRequest()->getParam('store_id') ? $this->getRequest()->getParam('store_id') : '1';

        $core_config = new Mage_Core_Model_Config();
        try {
            $core_config->saveConfig('autocompleteplus/config/layered', "1", $scope, $store_id);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            echo json_encode(array('status' => 'error: ' . print_r($e->getMessage(), true)));
            return;
        }
        
        $resp = array('new_state' 	         => 1,
                      'status'               => 'ok'
        );
        echo json_encode($resp);
        return;
    }

    public function setLayeredSearchOffAction() {
        $authkey = $this->getRequest()->getParam('authentication_key') ? $this->getRequest()->getParam('authentication_key') : "";
        $uuid = $this->getRequest()->getParam('uuid') ? $this->getRequest()->getParam('uuid') : "";
        if (!$this->valid($uuid, $authkey)) {
            echo json_encode(array('status' => 'error: ' . "Authentication failed"));
            return;
        }

        $scope = $this->getRequest()->getParam('scope') ? $this->getRequest()->getParam('scope') : 'stores';
        $store_id = $this->getRequest()->getParam('store_id') ? $this->getRequest()->getParam('store_id') : '1';

        $core_config = new Mage_Core_Model_Config();
        try {
            $core_config->saveConfig('autocompleteplus/config/layered', "0", $scope, $store_id);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            echo json_encode(array('status' => 'error: ' . print_r($e->getMessage(), true)));
            return;
        }
        
        $resp = array('new_state' 	         => 0,
                      'status'               => 'ok'
        );
        echo json_encode($resp);
        return;
    }

    public function getLayeredSearchConfigAction() {
        $authkey = $this->getRequest()->getParam('authentication_key') ? $this->getRequest()->getParam('authentication_key') : "";
        $uuid = $this->getRequest()->getParam('uuid') ? $this->getRequest()->getParam('uuid') : "";
        if (!$this->valid($uuid, $authkey)) {
            echo json_encode(array('status' => 'error: ' . "Authentication failed"));
            return;
        }
        $store_id = $this->getRequest()->getParam('store_id') ? $this->getRequest()->getParam('store_id') : '1';
        try {
            Mage::app()->getCacheInstance()->cleanType('config');
            $current_state = Mage::getStoreConfig('autocompleteplus/config/layered', $store_id);
        } catch (Exception $e) {
            echo json_encode(array('status' => 'error: ' . print_r($e->getMessage(), true)));
            return;
        }
        echo json_encode(array('current_state' => $current_state));
        return;
    }

    private function valid($uuid, $authkey) {
        $valid = false;
        try {
            $config_arr = Mage::getModel('autocompleteplus_autosuggest/config')->getCollection()->getData();
        } catch (Exception $e) {
            return $valid;
        }
        $config = $config_arr[0];
        if (isset($config['authkey']) && isset($config['licensekey'])) {
            if ($config['authkey'] == $authkey && $config['licensekey'] == $uuid) {
                $valid = true;
            }
        } else {
            return $valid;
        }
        return $valid;
    }
}
