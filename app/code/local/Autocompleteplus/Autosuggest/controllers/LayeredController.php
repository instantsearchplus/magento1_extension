<?php
/**
 * Autocompleteplus_Autosuggest_LayeredController
 * Used in creating options for Yes|No config value selection.
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category Mage
 *
 * @package   Instantsearchplus
 * @author    Fast Simon <info@instantsearchplus.com>
 * @copyright 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */
class Autocompleteplus_Autosuggest_LayeredController extends Mage_Core_Controller_Front_Action
{
    /**
     * Set headers
     *
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->getResponse()->clearHeaders();
        $this->getResponse()->setHeader('Content-type', 'application/json');
    }

    /**
     * Get ext config
     *
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    /**
     * Switches on layered search
     *
     * @return void
     */
    public function setLayeredSearchOnAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scope = $request->getParam('scope', 'stores');
        $scopeId = $request->getParam('store_id', 1);
        $mini_form_url_instantsearchplus = $request->getParam('mini_form_url_instantsearchplus', '0');

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: '.'Authentication failed')
            );
            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->enableLayeredNavigation($scope, $scopeId);
            if ($mini_form_url_instantsearchplus === '1') {
                $this->_getConfig()->enableMiniFormUrlRewrite($scope, $scopeId);
            } else {
                $this->_getConfig()->disableMiniFormUrlRewrite($scope, $scopeId);
            }

            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = array('new_state' => 1,
            'status' => 'ok',
        );

        $response->setBody(json_encode($resp));
    }

    /**
     * Switches off layered search
     *
     * @return void
     */
    public function setLayeredSearchOffAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scope = $request->getParam('scope', 'stores');
        $scopeId = $request->getParam('store_id', 1);

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array(
                    'status' => 'error: '.'Authentication failed'
                )
            );

            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->disableLayeredNavigation($scope, $scopeId);
            $this->_getConfig()->disableMiniFormUrlRewrite($scope, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = array('new_state' => 0,
            'status' => 'ok',
        );

        $response->setBody(json_encode($resp));
    }

    /**
     * Get layered configuration
     *
     * @return void
     */
    public function getLayeredSearchConfigAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scopeId = $request->getParam('store_id', 1);

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array(
                    'status' => $this->__('error: Authentication failed')
                )
            );
            $response->setBody($resp);

            return;
        }
        try {
            Mage::app()->getCacheInstance()->cleanType('config');
            $current_state = $this->_getConfig()->getLayeredNavigationStatus($scopeId);
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = json_encode(array('current_state' => $current_state));
        $response->setBody($resp);
    }

    /**
     * Switches smart navigation native on/off
     *
     * @return void
     */
    public function switchSmartNavigationNativeAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scope = $request->getParam('scope', 'stores');
        $scopeId = $request->getParam('store_id', 1);
        $state = $request->getParam('state');

        if (!in_array($state, array('on', 'off'))) {
            $resp = json_encode(
                array('status' => 'error: '.'Wrong state')
            );
            $response->setBody($resp);

            return;
        }

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: '.'Authentication failed')
            );
            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->switchSmartNavigationNative($state, $scope, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = array('new_state' => $state,
            'status' => 'ok',
        );

        $response->setBody(json_encode($resp));
    }

    public function setdropdownv2Action() {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scopeId = $request->getParam('store_id', 1);
        $drV2 = $request->getParam('v2_enabled', 'false');

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: '.'Authentication failed')
            );
            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->setDropdownV2($drV2, $scopeId);
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);
            Mage::logException($e);
            return;
        }

        $resp = array('new_state' => $drV2,
            'status' => 'ok',
        );
        $response->setBody(json_encode($resp));
    }

    public function setserpv2Action() {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scopeId = $request->getParam('store_id', 1);
        $drV2 = $request->getParam('v2_enabled', 'false');

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: '.'Authentication failed')
            );
            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->setSerpV2($drV2, $scopeId);
            if ($drV2 == 'true' || $drV2 == '1') {
                $helper = Mage::helper('autocompleteplus_autosuggest');
                $res = $helper->getSerpCustomValues($uuid, $scopeId);
                if ($res) {
                    $this->_getConfig()->setCustomValues($res, $scopeId);
                }
            }
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);
            Mage::logException($e);
            return;
        }

        $resp = array('new_state' => $drV2,
            'status' => 'ok',
        );
        $response->setBody(json_encode($resp));
    }

    public function setSmnV2Action() {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scopeId = $request->getParam('store_id', 1);
        $drV2 = $request->getParam('v2_enabled', 'false');

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: '.'Authentication failed')
            );
            $response->setBody($resp);

            return;
        }

        try {
            $this->_getConfig()->setSmnV2($drV2, $scopeId);
            if ($drV2 == 'true' || $drV2 == '1') {
                $helper = Mage::helper('autocompleteplus_autosuggest');
                $res = $helper->getSerpCustomValues($uuid, $scopeId);
                if ($res) {
                    $this->_getConfig()->setCustomValues($res, $scopeId);
                }
            }
            Mage::app()->getCacheInstance()->cleanType('config');
        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);
            Mage::logException($e);
            return;
        }

        $resp = array('new_state' => $drV2,
            'status' => 'ok',
        );
        $response->setBody(json_encode($resp));
    }

    public function setSerpCustomValuesAction() {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scopeId = $request->getParam('store_id', 1);

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: '.'Authentication failed')
            );
            $response->setBody($resp);
            return;
        }

        $command = 'https://dashboard.instantsearchplus.com/api/serving/magento_update_fields';
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $res = false;
        try {
            $res = $helper->getSerpCustomValues($uuid, $scopeId);
            if ($res) {
                $this->_getConfig()->setCustomValues($res, $scopeId);
                Mage::app()->getCacheInstance()->cleanType('config');
            }

        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: '.$e->getMessage()));
            $response->setBody($resp);
            Mage::logException($e);
            return;
        }

        $resp = array('new_state' => $res,
            'status' => 'ok',
        );
        $response->setBody(json_encode($resp));
    }
}
