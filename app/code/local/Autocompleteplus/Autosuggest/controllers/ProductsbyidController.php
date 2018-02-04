<?php
/**
 * InstantSearchPlus (Autosuggest)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 * @package    InstantSearchPlus
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Autocompleteplus_Autosuggest_ProductsbyidController extends Mage_Core_Controller_Front_Action
{
    const PHP_SCRIPT_TIMEOUT = 1800;
    const MISSING_PARAMETER = 767;
    const STATUS_FAILURE = 'failure';

    public function preDispatch()
    {
        parent::preDispatch();
        set_time_limit(self::PHP_SCRIPT_TIMEOUT);
    }

    public function getbyidAction()
    {
        $request  = $this->getRequest();
        $response = $this->getResponse();
        $storeId  = $request->getParam('store', 1);
        $id  = $request->getParam('id');

        if(!$id){
            $returnArr = array(
                'status'        => self::STATUS_FAILURE,
                'error_code'    => self::MISSING_PARAMETER,
                'error_details' => $this->__('The "id" parameter is mandatory')
            );
            $response->setHeader('Content-type', 'application/json');
            $response->setHttpResponseCode(400);
            $response->setBody(json_encode($returnArr));
            return;
        }

        $ids = explode(',', $id);
        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        $xml = $catalogModel->renderCatalogByIds($ids, $storeId);

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }

    public function getfromidAction()
    {
        $request  = $this->getRequest();
        $response = $this->getResponse();
        $fromId   = $request->getParam('id', 0);
        $storeId  = $request->getParam('store', 1);
        $count    = $request->getParam('count', 100);

        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        $xml = $catalogModel->renderCatalogFromIds($count, $fromId, $storeId);

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }
}