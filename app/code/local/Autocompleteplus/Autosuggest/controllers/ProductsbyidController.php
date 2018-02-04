<?php
/**
 * InstantSearchPlus (Autosuggest).
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 *
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Autocompleteplus_Autosuggest_ProductsbyidController extends Autocompleteplus_Autosuggest_Controller_Abstract
{
    public function getbyidAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $storeId = $request->getParam('store', 1);
        $id = $request->getParam('id');

        Mage::app()->setCurrentStore($storeId);

        if (!$id) {
            $returnArr = array(
                'status' => self::STATUS_FAILURE,
                'error_code' => self::MISSING_PARAMETER,
                'error_details' => $this->__('The "id" parameter is mandatory'),
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
        $request = $this->getRequest();
        $response = $this->getResponse();
        $fromId = $request->getParam('id', 0);
        $storeId = $request->getParam('store', 1);
        $count = $request->getParam('count', 100);

        Mage::app()->setCurrentStore($storeId);

        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        $xml = $catalogModel->renderCatalogFromIds($count, $fromId, $storeId);

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }
}
