<?php
/**
 * Batches.php File
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
 * @copyright 2017 Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */

/**
 * Autocompleteplus_Autosuggest_Helper_Batches
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
 * @copyright 2017 Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */
class Autocompleteplus_Autosuggest_Helper_Batches
{
    public function writeProductDeletion($sku, $productId, $storeId, $product = null)
    {
        /**
         * Filter out cases of item duplication where product id is null at the start
         */
        if ($productId == null) {
            return;
        }
        $dt = Mage::getSingleton('core/date')->gmtTimestamp();
        try {
            try {
                try {
                    if (!$product) {
                        $product = Mage::getModel('catalog/product')->load($productId);
                    }
                    $product_stores = ($storeId == 0 && method_exists($product, 'getStoreIds')) ? $product->getStoreIds() : array($storeId);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $product_stores = array($storeId);
                }
                if ($sku == null) {
                    $sku = 'dummy_sku';
                }
                foreach ($product_stores as $product_store) {
                    $batches = Mage::getModel('autocompleteplus_autosuggest/batches')->getCollection()
                        ->addFieldToFilter('product_id', $productId)
                        ->addFieldToFilter('store_id', $product_store);

                    $batches->getSelect()
                        ->order('update_date', 'DESC')
                        ->limit(1);

                    if ($batches->getSize() > 0) {
                        $batch = $batches->getFirstItem();
                        $batch->setUpdateDate($dt)
                            ->setAction('remove')
                            ->save();
                    } else {
                        $newBatch = Mage::getModel('autocompleteplus_autosuggest/batches');
                        $newBatch->setProductId($productId)
                            ->setStoreId($product_store)
                            ->setUpdateDate($dt)
                            ->setAction('remove')
                            ->setSku($sku)
                            ->save();
                    }
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $product_stores
     * @param $productId
     * @param $dt
     * @param $sku
     * @param $simple_product_parents
     */
    public function writeProductUpdate($product_stores, $productId, $dt, $sku, $simple_product_parents)
    {
        try {
            foreach ($product_stores as $product_store) {
                $updates = Mage::getModel('autocompleteplus_autosuggest/batches')->getCollection()
                    ->addFieldToFilter('product_id', $productId)
                    ->addFieldToFilter('store_id', $product_store);

                $updates->getSelect()
                    ->order('update_date', 'DESC')
                    ->limit(1);

                if ($updates && $updates->getSize() > 0) {
                    $row = $updates->getFirstItem();

                    $row->setUpdateDate($dt)
                        ->setAction('update');
                    $row->save();
                } else {
                    $batch = Mage::getModel('autocompleteplus_autosuggest/batches');
                    $batch->setProductId($productId)
                        ->setStoreId($product_store)
                        ->setUpdateDate($dt)
                        ->setAction('update')
                        ->setSku($sku);
                    $batch->save();
                }

                // trigger update for simple product's configurable parent
                if (!empty($simple_product_parents)) {   // simple product has configurable parent
                    foreach ($simple_product_parents as $configurable_product) {
                        $batches = Mage::getModel('autocompleteplus_autosuggest/batches')->getCollection()
                            ->addFieldToFilter('product_id', $configurable_product)
                            ->addFieldToFilter('store_id', $product_store);

                        $batches->getSelect()
                            ->order('update_date', 'DESC')
                            ->limit(1);

                        if ($batches->getSize() > 0) {
                            $batch = $batches->getFirstItem();
                            $batch->setUpdateDate($dt)
                                ->setAction('update')
                                ->save();
                        } else {
                            $newBatch = Mage::getModel('autocompleteplus_autosuggest/batches');
                            $newBatch->setProductId($configurable_product)
                                ->setStoreId($product_store)
                                ->setUpdateDate($dt)
                                ->setAction('update')
                                ->setSku('ISP_NO_SKU')
                                ->save();
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}