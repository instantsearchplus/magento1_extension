<?php

/**
 * Autocompleteplus_Autosuggest_RecommendationsController
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
class Autocompleteplus_Autosuggest_RecommendationsController extends Mage_Core_Controller_Front_Action
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
     * Set email recommendations value
     *
     * @return void
     */
    public function setEmailRecsAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $authkey = $request->getParam('authentication_key');
        $uuid = $request->getParam('uuid');
        $scope = $request->getParam('scope', 'stores');
        $scopeId = $request->getParam('store_id', 1);
        $email_recs_isp = $request->getParam('email_recs_isp', '0');

        if (!Mage::helper('autocompleteplus_autosuggest')->validate_auth($uuid, $authkey)) {
            $resp = json_encode(
                array('status' => 'error: ' . 'Authentication failed')
            );
            $response->setBody($resp);

            return;
        }

        try {
            $isEmailRecs = ( $email_recs_isp == '1' )? 1 : 0;

            Mage::app()->getCacheInstance()->cleanType('config');
            $this->_getConfig()->setEmailRecsStatus($scope, $scopeId, $isEmailRecs);

        } catch (Exception $e) {
            $resp = json_encode(array('status' => 'error: ' . $e->getMessage()));
            $response->setBody($resp);

            Mage::logException($e);

            return;
        }

        $resp = array('new_state' => $isEmailRecs,
            'status' => 'ok',
        );

        $response->setBody(json_encode($resp));
    }
}
