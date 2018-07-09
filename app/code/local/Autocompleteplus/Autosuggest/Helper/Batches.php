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
    public function writeProductDeletion(
        $sku,
        $productId,
        $simple_product_parents = null,
        $product_stores = null
    ) {
        /**
         * Filter out cases of item duplication where product id is null at the start
         */
        if ($productId == null) {
            return;
        }
        $dt = Mage::getSingleton('core/date')->gmtTimestamp();
        try {
            try {
                if (!$product_stores) {
                    $product_stores = $this->getProductStoresById($productId);
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

                    // trigger update for simple product's configurable parent
                    if (!empty($simple_product_parents)) {   // simple product has configurable parent
                        $this->update_parents($simple_product_parents, $product_store, $dt);
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
    public function writeProductUpdate($productId, $dt, $sku, $simple_product_parents, $product_stores = null)
    {
        try {
            if (!$product_stores) {
                $product_stores = $this->getProductStoresById($productId);
            }
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
                    $this->update_parents($simple_product_parents, $product_store, $dt);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $simple_product_parents
     * @param $product_store
     * @param $dt
     * @return array
     * @throws Varien_Exception
     */
    private function update_parents($simple_product_parents, $product_store, $dt)
    {
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

    /**
     * @param $product
     * @return array
     * @throws Varien_Exception
     */
    public function get_parent_products_ids($product)
    {
        if (is_numeric($product)) {
            $product_id = $product;
        } else {
            $product_id = $product->getID();
        }
        $simple_product_parents = Mage::getModel('catalog/product_type_configurable')
                ->getParentIdsByChild($product_id);
        if ($simple_product_parents == null) {
            $simple_product_parents = array();
        }
        $grouped_parents = Mage::getResourceSingleton('catalog/product_link')
            ->getParentIdsByChild($product_id, Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED);
        $bundle_ids = Mage::getResourceSingleton('bundle/selection')->getParentIdsByChild($product_id);
        return array_merge($simple_product_parents, $grouped_parents, $bundle_ids);
    }

    private function getProductStoresById($product_id) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $catalog_product_website_table_name = $resource->getTableName('catalog_product_website');

        $params = array();
        $query = "select *";
        $query .= " from";
        $query .= sprintf(" `%s`", $catalog_product_website_table_name);
        $query .= " where";
        $query .= sprintf(" `%s`.`product_id` = :product_id", $catalog_product_website_table_name);

        $product_id_param = new Varien_Db_Statement_Parameter($product_id);
        $product_id_param->setDataType(PDO::PARAM_INT);
        $params['product_id'] = $product_id_param;
        $results = $readConnection->fetchAll($query, $params);
        $storeIds = array();
        foreach ($results as $row) {
            $websiteStores = Mage::app()->getWebsite($row['website_id'])->getStoreIds();
            $storeIds = array_merge($storeIds, $websiteStores);
        }

        return $storeIds;
    }
}