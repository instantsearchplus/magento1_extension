<?php

class Autocompleteplus_Autosuggest_Model_Catalog extends Mage_Core_Model_Abstract
{
    protected $imageField;
    protected $standardImageFields = array('image', 'small_image', 'thumbnail');
    protected $useAttributes;
    protected $attributes;
    protected $currency;
    protected $pageNum;
    protected $_productCollection;
    protected $_xmlGenerator;
    protected $_helper;
    protected $_attributes;

    public function getXmlGenerator()
    {
        if (!$this->_xmlGenerator) {
            $this->_xmlGenerator = new Autocompleteplus_Autosuggest_Xml_Generator();
        }

        return $this->_xmlGenerator;
    }

    public function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('autocompleteplus_autosuggest');
        }

        return $this->_helper;
    }

    public function getAttributes()
    {
        if (!$this->_attributes) {
            $productModel = Mage::getModel('catalog/product');
            $this->_attributes = Mage::getResourceModel(
                'eav/entity_attribute_collection'
            )
                ->setEntityTypeFilter($productModel->getResource()->getTypeId())
                ->addFieldToFilter('is_user_defined', '1');
        }

        return $this->_attributes;
    }

    public function getProductCollection($new = false)
    {
        if (!$this->_productCollection) {
            $this->_productCollection = Mage::getModel('catalog/product')
                ->getCollection();
        }

        if ($new === true) {
            return Mage::getModel('catalog/product')->getCollection();
        }

        return $this->_productCollection;
    }

    public function getProductRenderer()
    {
        return Mage::getSingleton(
            'autocompleteplus_autosuggest/renderer_catalog_product'
        );
    }

    public function getBatchRenderer()
    {
        return Mage::getSingleton('autocompleteplus_autosuggest/renderer_batches');
    }

    /**
     * GetAllProductIds
     *
     * Get all product ids from loaded products collection
     *
     * @return array
     */
    public function getAllProductIds()
    {
        $ids = array();

        foreach ($this->getProductCollection() as $product) {
            $ids[] = $product->getID();
        }
        return $ids;
    }

    /**
     * GetOrdersPerProduct
     *
     * Get orders information for products from loaded product
     * collection
     *
     * @return array
     */
    public function getOrdersPerProduct()
    {
        $productIds = implode(',', $this->getAllProductIds());
        $salesOrderItemCollection = Mage::getResourceModel(
            'sales/order_item_collection'
        );
        $salesOrderItemCollection->getSelect()->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('product_id', 'SUM(qty_ordered) as qty_ordered'))
            ->where(new Zend_Db_Expr('store_id = '.$this->getStoreId()))
            ->where(new Zend_Db_Expr('product_id IN ('.$productIds.')'))
            ->where(
                new Zend_Db_Expr(
                    'created_at BETWEEN NOW() - INTERVAL '.
                    $this->getMonthInterval().
                    ' MONTH AND NOW()'
                )
            )
            ->group(array('product_id'));

        $products = array();

        foreach ($salesOrderItemCollection as $item) {
            $products[$item['product_id']] = (int)$item['qty_ordered'];
        }

        return $products;
    }

    public function renderCatalogXml(
        $startInd = 0,
        $count = 10000,
        $storeId = false,
        $orders = false,
        $monthInterval = 12
    ) {
        $xmlGenerator = $this->getXmlGenerator();
        $count = ($count > 10000) ? 10000 : $count;
        $this->setStoreId($storeId);
        $this->setOrders($orders);
        $this->setMonthInterval($monthInterval);

        $xmlGenerator->setRootAttributes(
            array(
                'version' => $this->getHelper()->getVersion(),
                'magento' => $this->getHelper()->getMageVersion(),
            )
        )->setRootElementName('catalog');

        $productCollection = $this->getProductCollection();

        $productCollection->getSelect()->limit($count, $startInd);
        if (is_numeric($storeId)) {
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
        }

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToSelect($attributesToSelect)
            ->addMinimalPrice()
            ->addFinalPrice();

        Mage::getModel('review/review')->appendSummary($productCollection);

        if ($this->getOrders()) {
            $ordersData = $this->getOrdersPerProduct();
            $this->getProductRenderer()
                ->setOrderData($ordersData);
        }

        foreach ($productCollection as $product) {
            $this->getProductRenderer()
                ->setAction('insert')
                ->setProduct($product)
                ->setStoreId($this->getStoreId())
                ->setOrders($this->getOrders())
                ->setMonthInterval($this->getMonthInterval())
                ->setXmlElement($xmlGenerator)
                ->setAttributes($this->getAttributes())
                ->renderXml();
        }

        return $xmlGenerator->generateXml();
    }

    public function canUseAttributes()
    {
        if (!$this->_useAttributes) {
            $this->_useAttributes = Mage::getStoreConfigFlag(
                'autocompleteplus/config/attributes'
            );
        }

        return $this->_useAttributes;
    }

    public function getSingleBatchTableRecord($id)
    {
        $batches = array();
        $updates = $this->getSingleBatcheCollection($id);
        $max_update_date = 0;
        foreach ($updates as $batch) {
            if (intval($batch['update_date']) > $max_update_date) {
                $max_update_date = $batch['update_date'];
            }
            $batches[] = array(
                'product_id' => $batch['product_id'],
                'action' => $batch['action'],
                'update_date' => $batch['update_date'],
                'store_id' => $batch['store_id']
            );
        }

        return json_encode(
            array(
                'max_update_date' => $max_update_date,
                'batches' => $batches
            )
        );
    }

    public function getBatchesTableRecords($count, $from, $to, $storeId, $page)
    {
        $batches = array();
        $filter = array('from' => $from);
        if ($to > 0) {
            $filter['to'] = $to;
        }
        $max_update_date = 0;
        $rows_count = 0;
        $updates = $this->getBatchesCollection($count, $storeId, $page, $filter);
        foreach ($updates as $batch) {
            $rows_count++;
            if (intval($batch['update_date']) > $max_update_date) {
                $max_update_date = $batch['update_date'];
            }
            $batches[] = array(
                'product_id' => $batch['product_id'],
                'action' => $batch['action'],
                'update_date' => $batch['update_date'],
                'store_id' => $batch['store_id']
            );
        }

        return json_encode(
            array(
                'max_update_date' => $max_update_date,
                'rows_count' => $rows_count,
                'batches' => $batches
            )
        );
    }

    public function renderUpdatesCatalogXml($count, $from, $to, $storeId, $page)
    {
        $filter = array('from' => $from);
        if ($to > 0) {
            $filter['to'] = $to;
        }
        $updates = $this->getBatchesCollection($count, $storeId, $page, $filter);
        $xmlGenerator = $this->getXmlGenerator();

        $xmlGenerator->setRootAttributes(
            array(
                'version' => $this->getHelper()->getVersion(),
                'magento' => $this->getHelper()->getMageVersion(),
                'fromdatetime' => $from,
            )
        )->setRootElementName('catalog');

        $updatesBulk = array();

        $productIds = array();
        $all_pr_ids = array();
        $xml_indexed_ids = array();
        foreach ($updates as $batch) {
            $all_pr_ids[] = $batch['product_id'];
            if ($batch['action'] == 'update') {
                if ($batch['product_id'] != null) {
                    $updatesBulk[$batch['product_id']] = $batch;

                    $productIds[] = $batch['product_id'];
                } else {
                    $xml_indexed_ids[] = $batch['product_id'];
                    $batch['action'] = 'remove';
                    $this->getBatchRenderer()
                        ->setXmlElement($xmlGenerator)
                        ->makeRemoveRow($batch);
                }
            } elseif ($batch['action'] == 'remove') {
                $xml_indexed_ids[] = $batch['product_id'];
                $this->getBatchRenderer()
                    ->setXmlElement($xmlGenerator)
                    ->makeRemoveRow($batch);
            }
        }

        $this->currency = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();

        $productCollection = $this->getProductCollection();

        $productCollection->addStoreFilter($storeId);

        $productCollection->setStoreId($storeId);

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToSelect($attributesToSelect)
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
            ->addMinimalPrice()
            ->addFinalPrice();

        foreach ($productCollection as $product) {
            $updatedate = $updatesBulk[$product->getId()]['update_date'];
            $xml_indexed_ids[] = $product->getId();
            $this->getProductRenderer()
                ->setXmlElement($xmlGenerator)
                ->setAction('update')
                ->setProduct($product)
                ->setStoreId($storeId)
                ->setOrders($this->getOrders())
                ->setMonthInterval($this->getMonthInterval())
                ->setXmlElement($xmlGenerator)
                ->setAttributes($this->getAttributes())
                ->setUpdateDate($updatedate)
                ->renderXml();
        }
        $not_indexed_ids = array();
        if (count($all_pr_ids) != count($xml_indexed_ids)) {
            foreach ($all_pr_ids as $id) {
                if (!in_array($id, $xml_indexed_ids)) {
                    $not_indexed_ids[] = $id;
                }
            }
            $productCollection = $this->getProductCollection(true);
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
            $productCollection->addAttributeToSelect($attributesToSelect)
                ->addAttributeToFilter('entity_id', array('in' => $not_indexed_ids));

            foreach ($productCollection as $product) {
                $updatedate = $updatesBulk[$product->getId()]['update_date'];
                $this->getProductRenderer()
                    ->setXmlElement($xmlGenerator)
                    ->setAction('update')
                    ->setProduct($product)
                    ->setStoreId($storeId)
                    ->setOrders($this->getOrders())
                    ->setMonthInterval($this->getMonthInterval())
                    ->setXmlElement($xmlGenerator)
                    ->setAttributes($this->getAttributes())
                    ->setUpdateDate($updatedate)
                    ->renderXml();
            }
        }
        return $xmlGenerator->generateXml();
    }

    public function renderCatalogFromIds($count, $fromId, $storeId)
    {
        $xmlGenerator = $this->getXmlGenerator();
        $xmlGenerator->setRootAttributes(array(
            'version' => $this->getHelper()->getVersion(),
            'magento' => $this->getHelper()->getMageVersion(),
        ))->setRootElementName('catalog');

        $productCollection = $this->getProductCollection();
        if (is_numeric($storeId)) {
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
        }

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToFilter(
            'entity_id',
            array('from' => $fromId)
        );

        $productCollection->addAttributeToSelect($attributesToSelect)
            ->addMinimalPrice()
            ->addFinalPrice();

        $productCollection->setPageSize($count);
        $productCollection->setCurPage(1);

        Mage::getModel('review/review')->appendSummary($productCollection);

        foreach ($productCollection as $product) {
            $this->getProductRenderer()
                ->setAction('getfromid')
                ->setProduct($product)
                ->setStoreId($storeId)
                ->setXmlElement($xmlGenerator)
                ->setAttributes($this->getAttributes())
                ->setGetByIdStatus(1)
                ->renderXml();
        }

        return $xmlGenerator->generateXml();
    }

    /**
     * Creates an XML representation of catalog by ids.
     *
     * @param array $ids
     * @param int   $storeId
     *
     * @return string
     */
    public function renderCatalogByIds($ids, $storeId = 0, $force=false)
    {
        $xmlGenerator = $this->getXmlGenerator();
        $xmlGenerator->setRootAttributes(
            array(
                'version' => $this->getHelper()->getVersion(),
                'magento' => $this->getHelper()->getMageVersion(),
            )
        )->setRootElementName('catalog');

        $productCollection = $this->getProductCollection();
        if (is_numeric($storeId)) {
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
        }

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToFilter('entity_id', array('in' => $ids));

        $productCollection->addAttributeToSelect($attributesToSelect);

        if (!$force) {
            $productCollection->addMinimalPrice()
                ->addFinalPrice();
        }

        Mage::getModel('review/review')->appendSummary($productCollection);

        foreach ($productCollection as $product) {
            $this->getProductRenderer()
                ->setAction('getbyid')
                ->setProduct($product)
                ->setStoreId($storeId)
                ->setXmlElement($xmlGenerator)
                ->setAttributes($this->getAttributes())
                ->setGetByIdStatus(1)
                ->renderXml();
        }

        return $xmlGenerator->generateXml();
    }

    /**
     * GetAttributesToSelect
     *
     * @return array
     */
    protected function _getAttributesToSelect()
    {
        $externalImage = $this->getProductRenderer()->getImageField();
        $attributesToSelect = array(
            'store_id',
            'name',
            'description',
            'short_description',
            'visibility',
            'thumbnail',
            'image',
            'small_image',
            'url',
            'status',
            'updated_at',
            'price',
            'meta_title',
            'meta_description',
            'special_price',
            'special_from_date',
            'special_to_date',
            'news_from_date',
            'news_to_date',
            'sku',
            'tier_price',
            'price_type'
        );

        if ($externalImage != null && $externalImage != '') {
            $attributesToSelect[] = $externalImage;
        }

        if ($this->canUseAttributes()) {
            foreach ($this->getAttributes() as $attr) {
                $action = $attr->getAttributeCode();

                $attributesToSelect[] = $action;
            }

            return $attributesToSelect;
        }

        return $attributesToSelect;
    }

    private function getSingleBatcheCollection($id)
    {
        $updates = Mage::getModel('autocompleteplus_autosuggest/batches')
            ->getCollection()
            ->addFieldToFilter('product_id', $id);

        $updates->setOrder('update_date', 'ASC');

        return $updates;
    }

    /**
     * @param $count
     * @param $storeId
     * @param $page
     * @param $filter
     * @return mixed
     */
    private function getBatchesCollection($count, $storeId, $page, $filter)
    {
        $updates = Mage::getModel('autocompleteplus_autosuggest/batches')
            ->getCollection()
            ->addFieldToFilter('update_date', $filter)
            ->addFieldToFilter('store_id', $storeId);

        $this->setStoreId($storeId);
        $updates->setOrder('update_date', 'ASC');

        $updates->setPageSize($count);
        $updates->setCurPage($page);
        return $updates;
    }
}
