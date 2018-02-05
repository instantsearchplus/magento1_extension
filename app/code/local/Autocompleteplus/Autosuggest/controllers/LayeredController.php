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
        
        if (!$this->valid($uuid, $authkey)) {
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

        if (!$this->valid($uuid, $authkey)) {
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

        if (!$this->valid($uuid, $authkey)) {
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
     * Checks if uuid is valid
     *
     * @param string $uuid
     * @param string $authkey
     *
     * @return bool
     */
    protected function valid($uuid, $authkey)
    {
        if ($this->_getConfig()->getAuthorizationKey() == $authkey
            && $this->_getConfig()->getUUID() == $uuid
        ) {
            return true;
        }

        return false;
    }
}
