<?php

class Autocompleteplus_Autosuggest_Model_Catalogreport extends Mage_Core_Model_Abstract
{
    protected $_storeId;

    public function getDisabledProductsCount()
    {
        try {
            $collection = $this->getProductCollectionStoreFilterFactory();
            $this->addDisabledFilterToCollection($collection);

            return $collection->getSize();
        } catch (Exception $e) {
            return -1;
        }
    }

    public function getEnabledProductsCount()
    {
        try {
            $collection = $this->getProductCollectionStoreFilterFactory();
            $this->addEnabledFilterToCollection($collection);

            return $collection->getSize();
        } catch (Exception $e) {
            return -1;
        }
    }

    public function getSearchableProductsIds()
    {
        $collection = $this->getProductCollectionStoreFilterFactory();
        $this->addEnabledFilterToCollection($collection);
        $this->addVisibleInSearchFilterToCollection($collection);
        $ids = array();
        foreach($collection as $product) {
            $ids[] = array(
                'id' => $product->getID(),
                'sku' => $product->getSku()
            );
        }
        return $ids;
    }

    public function getSearchableProductsCount($show_out_of_stock)
    {
        try {
            $collection = $this->getProductCollectionStoreFilterFactory();
            if (!$show_out_of_stock) {
                $collection->addMinimalPrice()
                    ->addFinalPrice();
            }
            $this->addEnabledFilterToCollection($collection);
            $this->addVisibleInSearchFilterToCollection($collection);

            return $collection->getSize();
        } catch (Exception $e) {
            return -1;
        }
    }

    public function getSearchableProducts2Count()
    {
        try {
            $num_of_searchable_products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($this->getCurrentStoreId())
                ->addAttributeToFilter('status', array('eq' => 1))          // Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                ->addAttributeToFilter(array(
                    array('attribute' => 'visibility', 'finset' => 3),  // visibility Search
                    array('attribute' => 'visibility', 'finset' => 4),  // visibility Catalog, Search
                ))
                ->getSize();

            return $num_of_searchable_products;
        } catch (Exception $e) {
            return -1;
        }
    }

    protected function getProductCollectionStoreFilterFactory()
    {
        return Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->getCurrentStoreId());
    }

    public function addEnabledFilterToCollection($collection)
    {
        return $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
    }

    public function addDisabledFilterToCollection($collection)
    {
        return $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED));
    }

    public function addVisibleInCatalogFilterToCollection($collection)
    {
        return Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
    }

    public function addVisibleInSearchFilterToCollection($collection)
    {
        return Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
    }

    /**
     * Return the current store - can be overridden with post.
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        if (!$this->_storeId) {
            $post = $this->getRequest()->getParams();
            if (array_key_exists('store_id', $post)) {
                $this->_storeId = $post['store_id'];
            } elseif (array_key_exists('store', $post)) {
                $this->_storeId = $post['store'];
            } else {
                $this->_storeId = Mage::app()->getStore()->getStoreId();
            }
        }

        return $this->_storeId;
    }

    public function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * @param $store
     * @param $customer_group
     * @param $count
     * @param $startInd
     * @return array
     * @throws Varien_Exception
     */
    public function getPricesFromIndex($store, $customer_group, $count, $startInd, $product_id)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $price_index_table_name = $resource->getTableName('catalog_product_index_price');
        $eav_table_name = $resource->getTableName('eav_attribute');
        $entity_int_table_name = $resource->getTableName('catalog_product_entity_int');
        $product_entity_table_name = $resource->getTableName('catalog_product_entity');
        $params = array();
        $limit = ' LIMIT :limit';
        $offset = ' OFFSET :offset';
        $website_id = Mage::getModel('core/store')->load($store)->getWebsiteId();

        $query = "select";
        $query .= sprintf(" `%s`.`attribute_id` AS `attribute_id`,", $eav_table_name);
        $query .= sprintf(" `%s`.`entity_id` AS `entity_id`,", $entity_int_table_name);
        $query .= sprintf(" `%s`.`value` AS `value`,", $entity_int_table_name);
        $query .= sprintf(" `%s`.`attribute_code` AS `attribute_code`,", $eav_table_name);
        $query .= sprintf(" `%s`.`type_id` AS `type_id`,", $product_entity_table_name);
        $query .= sprintf(" `%s`.*", $price_index_table_name);
        $query .= " from";
        $query .= sprintf(" `%s`", $eav_table_name);
        $query .= sprintf(" join `%s` on `%s`.`attribute_id` = `%s`.`attribute_id`", $entity_int_table_name, $eav_table_name, $entity_int_table_name);
        $query .= sprintf(" join `%s` on `%s`.`entity_id` = `%s`.`entity_id`", $price_index_table_name, $entity_int_table_name, $price_index_table_name);
        $query .= sprintf(" join `%s` on `%s`.`entity_id` = `%s`.`entity_id`", $product_entity_table_name, $product_entity_table_name, $price_index_table_name);
        $query .= " where";
        $query .= sprintf(" ((`%s`.`attribute_code` = 'visibility') and", $eav_table_name);
        $query .= sprintf(" (`%s`.`value` in (3,4)) and", $entity_int_table_name);
        $query .= sprintf(" (`%s`.`customer_group_id` = :customer_group) and", $price_index_table_name);
        $query .= sprintf(" (`%s`.`website_id` = %d))", $price_index_table_name, $website_id);
//        $query .= sprintf(" AND (`%s`.`type_id` = 'simple')", $product_entity_table_name);

        if ($product_id > 0) {
            $query .= sprintf(" and (`%s`.`entity_id` = :product_id)", $price_index_table_name);
            $product_id_param = new Varien_Db_Statement_Parameter($product_id);
            $product_id_param->setDataType(PDO::PARAM_INT);
            $params['product_id'] = $product_id_param;
        }

        $query .= $limit;
        $query .= $offset;

        $customer_group_param = new Varien_Db_Statement_Parameter($customer_group);
        $customer_group_param->setDataType(PDO::PARAM_INT);
        $params['customer_group'] = $customer_group_param;

        $limit_param = new Varien_Db_Statement_Parameter((int)$count);
        $limit_param->setDataType(PDO::PARAM_INT);
        $params['limit'] = $limit_param;

        $offset_param = new Varien_Db_Statement_Parameter((int)$startInd);
        $offset_param->setDataType(PDO::PARAM_INT);
        $params['offset'] = $offset_param;

        $results = $readConnection->fetchAll($query, $params);
        $result = array();
        foreach ($results as $res) {
            $result[$res['entity_id']] = array(
                'id' => $res['entity_id'],
                'final_price' => $res['final_price'],
                'min_price' => $res['min_price'],
                'tier_price' => $res['tier_price'],
                'group_price' => $res['group_price'],
                'website_id' => $res['website_id'],
                'customer_group_id' => $res['customer_group_id'],
                'type_id' => $res['type_id']
            );
        }
        return $result;
    }
}
