<?php

class Autocompleteplus_Autosuggest_LayeredController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        $this->getResponse()->clearHeaders();
        $this->getResponse()->setHeader('Content-type', 'application/json');
    }

    protected function _getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    public function setLayeredSearchOnAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scope = $request->getParam('scope', 'stores');
        $scopeId = $request->getParam('store_id', 1);

        if (!$this->valid($uuid, $authkey)) {
            $resp = json_encode(array('status' => 'error: '.'Authentication failed'));
            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->enableLayeredNavigation($scope, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.print_r($e->getMessage(), true)));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = array('new_state' => 1,
                      'status' => 'ok',
        );

        $response->setBody(json_encode($resp));
    }

    public function setLayeredSearchOffAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scope = $request->getParam('scope', 'stores');
        $scopeId = $request->getParam('store_id', 1);

        if (!$this->valid($uuid, $authkey)) {
            $resp = json_encode(array('status' => 'error: '.'Authentication failed'));

            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->disableLayeredNavigation($scope, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.print_r($e->getMessage(), true)));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = array('new_state' => 0,
                      'status' => 'ok',
        );

        $response->setBody(json_encode($resp));
    }

    public function getLayeredSearchConfigAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scopeId = $request->getParam('store_id', 1);

        if (!$this->valid($uuid, $authkey)) {
            $resp = json_encode(array('status' => $this->__('error: Authentication failed')));
            $response->setBody($resp);

            return;
        }
        try {
            Mage::app()->getCacheInstance()->cleanType('config');
            $current_state = $this->_getConfig()->getLayeredNavigationStatus($scopeId);
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.print_r($e->getMessage(), true)));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = json_encode(array('current_state' => $current_state));
        $response->setBody($resp);
    }

    protected function valid($uuid, $authkey)
    {
        if ($this->_getConfig()->getAuthorizationKey() == $authkey
            && $this->_getConfig()->getUUID() == $uuid) {
            return true;
        }

        return false;
    }
}
