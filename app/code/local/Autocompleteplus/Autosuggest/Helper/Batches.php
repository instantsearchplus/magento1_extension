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
    const AUTOSUGGEST_BATCH_TABLE_NAME = 'autocompleteplus_batches';

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
                    $product_stores = $this->getProductStoresById($productId, $simple_product_parents);
                }
                if ($sku == null) {
                    $sku = 'dummy_sku';
                }
                foreach ($product_stores as $product_store) {
                    $data = [
                        'product_id'=> $productId,
                        'store_id'=> $product_store,
                        'update_date'=> $dt,
                        'action'=> 'remove',
                        'sku'=> $sku
                    ];
                    $this->upsertData($data);
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
        if (!$productId) {
            return;
        }
        try {
            if (!$product_stores || (is_array($product_stores) && count($product_stores) == 1 && $product_stores[0] == 0)) {
                $product_stores = $this->getProductStoresById($productId, $simple_product_parents);
            }

            foreach ($product_stores as $product_store) {
                $data = [
                    'product_id'=> $productId,
                    'store_id'=> $product_store,
                    'update_date'=> $dt,
                    'action'=> 'update',
                    'sku'=> $sku
                ];
                $this->upsertData($data);

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
     * @param $rows
     * @param $product_ids
     * @return void
     * @throws Varien_Exception
     */
    public function writeMassProductsUpdate($product_ids, $rows)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $batches_table_name = $resource->getTableName(self::AUTOSUGGEST_BATCH_TABLE_NAME);

        $where = sprintf(" `product_id` in (%s)", join(',', $product_ids));

        $writeConnection->delete($batches_table_name, $where);

        $writeConnection->insertMultiple($batches_table_name, $rows);
    }

    /**
     * @param $simple_product_parents
     * @param $product_store
     * @param $dt
     * @return void
     * @throws Varien_Exception
     */
    private function update_parents($simple_product_parents, $product_store, $dt)
    {
        foreach ($simple_product_parents as $configurable_product) {
            $data = [
                'product_id'=> $configurable_product,
                'store_id'=> $product_store,
                'update_date'=> $dt,
                'action'=> 'update',
                'sku'=> 'ISP_NO_SKU'
            ];
            $this->upsertData($data);
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

    public function getAllProductIdsByWebsiteId($website_id) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $catalog_product_website_table_name = $resource->getTableName('catalog_product_website');

        $params = array();
        $query = "select product_id";
        $query .= " from";
        $query .= sprintf(" `%s`", $catalog_product_website_table_name);
        $query .= " where";
        $query .= sprintf(" (`%s`.`website_id` in (:website_id)", $catalog_product_website_table_name);

        $website_id_param = new Varien_Db_Statement_Parameter($website_id);
        $website_id_param->setDataType(PDO::PARAM_INT);
        $params['website_id'] = $website_id_param;

        $query .= ")";
        $results = $readConnection->fetchAll($query, $params);
        $productIds = array();
        foreach ($results as $row) {
            $productIds[] = $row['product_id'];
        }

        return $productIds;
    }

    public function getProductStoresById($product_id, $simple_product_parents) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $catalog_product_website_table_name = $resource->getTableName('catalog_product_website');

        $params = array();
        $query = "select *";
        $query .= " from";
        $query .= sprintf(" `%s`", $catalog_product_website_table_name);
        $query .= " where";
        $query .= sprintf(" (`%s`.`product_id` in (:product_id)", $catalog_product_website_table_name);

        $product_id_param = new Varien_Db_Statement_Parameter($product_id);
        $product_id_param->setDataType(PDO::PARAM_INT);
        $params['product_id'] = $product_id_param;

        $counter = 1;
        if ($simple_product_parents != null) {
            foreach ($simple_product_parents as $parent_id) {
                $query .= sprintf(" or `%s`.`product_id` in (:product_id_%s)", $catalog_product_website_table_name, $counter);
                $product_id_param = new Varien_Db_Statement_Parameter($parent_id);
                $product_id_param->setDataType(PDO::PARAM_INT);
                $params[sprintf('product_id_%s', $counter)] = $product_id_param;
                $counter++;
            }
        }

        $query .= ")";
        $results = $readConnection->fetchAll($query, $params);
        $storeIds = array();
        foreach ($results as $row) {
            $websiteStores = Mage::app()->getWebsite($row['website_id'])->getStoreIds();
            $storeIds = array_merge($storeIds, $websiteStores);
        }

        return $storeIds;
    }

    /**
     * @param $table_name
     * @param $data
     */
    public function upsertData($data, $table_name=null)
    {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        if (!$table_name) {
            $table_name = self::AUTOSUGGEST_BATCH_TABLE_NAME;
        }
        $table_name = $resource->getTableName($table_name);
        try {
            $writeConnection->insertOnDuplicate($table_name, $data);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}