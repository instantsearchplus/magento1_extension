<?php
/**
 * ProductsbyidController File
 *
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

/**
 * Autocompleteplus_Autosuggest_ProductsbyidController
 *
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
class Autocompleteplus_Autosuggest_ProductsbyidController extends Autocompleteplus_Autosuggest_Controller_Abstract
{
    /**
     * GetbyidAction
     *
     * @return void
     */
    public function getbyidAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $storeId = $request->getParam('store', 1);
        $id = $request->getParam('id', false);
        $sku = $request->getParam('sku', false);
        $force = $request->getParam('force', false);

        Mage::app()->setCurrentStore($storeId);

        if ($force) {
            $process = Mage::helper('catalog/product_flat')->getProcess();
            $status = $process->getStatus();
            $process->setStatus(Mage_Index_Model_Process::STATUS_RUNNING);
        }


        if (!$id && !$sku) {
            $returnArr = array(
                'status' => self::STATUS_FAILURE,
                'error_code' => self::MISSING_PARAMETER,
                'error_details' => $this->__('The "id" or "sku" parameter is mandatory'),
            );
            $response->setHeader('Content-type', 'application/json');
            $response->setHttpResponseCode(400);
            $response->setBody(json_encode($returnArr));

            return;
        }
        $ids = null;
        if ($id)
            $ids = explode(',', $id);
        $skus = null;
        $sku = urldecode($sku);
        if ($sku)
            $skus = explode(',', $sku);
        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        $xml = $catalogModel->renderCatalogByIds($ids, $skus, $storeId, $force);

        if ($force) {
            $process->setStatus($status);
        }

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }

    /**
     * GetfromidAction
     *
     * @return void
     */
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
