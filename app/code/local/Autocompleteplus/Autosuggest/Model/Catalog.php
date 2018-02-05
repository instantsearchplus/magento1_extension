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
            $this->_attributes = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setEntityTypeFilter($productModel->getResource()->getTypeId())
                ->addFieldToFilter('is_user_defined', '1')
            ;
        }
        return $this->_attributes;
    }

    public function getProductCollection($new = false)
    {
        if (!$this->_productCollection) {
            $this->_productCollection = Mage::getModel('catalog/product')->getCollection();
        }

        if ($new === true) {
            return Mage::getModel('catalog/product')->getCollection();
        }

        return $this->_productCollection;
    }

    public function getProductRenderer()
    {
        return Mage::getSingleton('autocompleteplus_autosuggest/renderer_catalog_product');
    }

    public function getBatchRenderer()
    {
        return Mage::getSingleton('autocompleteplus_autosuggest/renderer_batches');
    }

    public function renderCatalogXml($startInd = 0,
                                     $count = 10000,
                                     $storeId = false,
                                     $orders = false,
                                     $monthInterval = 12,
                                     $checksum = false)
    {
        $xmlGenerator = $this->getXmlGenerator();
        $count = ($count > 10000) ? 10000 : $count;
        $this->setStoreId($storeId);
        $this->setOrders($orders);
        $this->setMonthInterval($monthInterval);
        $this->setChecksum($checksum);


        $xmlGenerator->setRootAttributes(array(
            'version'   =>  $this->getHelper()->getVersion(),
            'magento'   =>  $this->getHelper()->getMageVersion()
        ))->setRootElementName('catalog');

        $productCollection = $this->getProductCollection();

        // @codingStandardsIgnoreLine
        $productCollection->getSelect()->limit($count, $startInd);
        if (is_numeric($storeId)) {
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
        }

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToSelect($attributesToSelect);

        Mage::getModel('review/review')->appendSummary($productCollection);

        if ($this->getChecksum() !== false) {
            $this->setHasChecksum($checksum);
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
            if ($this->getHasChecksum()) {
                if ($this->getHelper()->isChecksumTableExists()) {
                    $checksum = $this->getHelper()->calculateChecksum($product);
                    $this->getHelper()->updateSavedProductChecksum($product->getId(), $product->getSku(), $this->getStoreId(), $checksum);
                }
            }
        }

        return $xmlGenerator->generateXml();
    }

    public function canUseAttributes()
    {
        if (!$this->_useAttributes) {
            $this->_useAttributes = Mage::getStoreConfigFlag('autocompleteplus/config/attributes');
        }
        return $this->_useAttributes;
    }

    public function renderUpdatesCatalogXml($count, $from, $to, $storeId)
    {
        $updates = Mage::getModel('autocompleteplus_autosuggest/batches')->getCollection()
            ->addFieldToFilter('update_date', array(
                'from'  =>  $from,
                'to'    =>  $to
            ))
            ->addFieldToFilter('store_id', $storeId);

        $this->setStoreId($storeId);
        $updates->setOrder('update_date', 'ASC');

        $updates->setPageSize($count);
        $updates->setCurPage(1);
        $xmlGenerator= $this->getXmlGenerator();

        $xmlGenerator->setRootAttributes(array(
            'version'   =>  $this->getHelper()->getVersion(),
            'magento'   =>  $this->getHelper()->getMageVersion(),
            'fromdatetime'  =>  $from
        ))->setRootElementName('catalog');

        foreach ($updates as $batch) {
            if ($batch['action'] == 'update') {
                $productId = $batch['product_id'];
                $batchStoreId = $batch['store_id'];

                if ($storeId != $batchStoreId) {
                    $this->currency = Mage::app()->getStore($batchStoreId)->getCurrentCurrencyCode();
                }

                $productModel = null;

                if ($productId != null) {
                    //                  load product by id
                    try {
                        $productModel = Mage::getModel('catalog/product')
                            ->setStoreId($batchStoreId)
                            ->load($productId);
                    } catch (Exception $e) {
                        $batch['action'] = 'remove';
                        $this->getBatchRenderer()
                            ->setXmlElement($xmlGenerator)
                            ->makeRemoveRow($batch);
                        continue;
                    }
                } else {
                    // product not found - changing action to remove
                    $batch['action'] = 'remove';
                    $this->getBatchRenderer()
                        ->setXmlElement($xmlGenerator)
                        ->makeRemoveRow($batch);
                    continue;
                }

                if ($productModel == null) {
                    continue;
                }

                $updatedate = $batch['update_date'];
                $action = $batch['action'];
                $this->getProductRenderer()
                    ->setXmlElement($xmlGenerator)
                    ->setAction($action)
                    ->setProduct($productModel)
                    ->setStoreId($this->getStoreId())
                    ->setOrders($this->getOrders())
                    ->setMonthInterval($this->getMonthInterval())
                    ->setXmlElement($xmlGenerator)
                    ->setAttributes($this->getAttributes())
                    ->setUpdateDate($updatedate)
                    ->renderXml();
            } else {
                $this->getBatchRenderer()
                    ->setXmlElement($xmlGenerator)
                    ->makeRemoveRow($batch);
            }
        }

        return $xmlGenerator->generateXml();
    }

    public function renderCatalogFromIds($count, $fromId, $storeId)
    {
        $xmlGenerator = $this->getXmlGenerator();
        $xmlGenerator->setRootAttributes(array(
            'version'   =>  $this->getHelper()->getVersion(),
            'magento'   =>  $this->getHelper()->getMageVersion()
        ))->setRootElementName('catalog');

        $productCollection = $this->getProductCollection();
        if (is_numeric($storeId)) {
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
        }

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToSelect($attributesToSelect);

        $productCollection->addAttributeToFilter('entity_id', array('from'  =>  $fromId));
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
    public function renderCatalogByIds($ids, $storeId = 0)
    {
        $xmlGenerator = $this->getXmlGenerator();
        $xmlGenerator->setRootAttributes(array(
            'version'   =>  $this->getHelper()->getVersion(),
            'magento'   =>  $this->getHelper()->getMageVersion()
        ))->setRootElementName('catalog');

        $productCollection = $this->getProductCollection();
        if (is_numeric($storeId)) {
            $productCollection->addStoreFilter($storeId);
            $productCollection->setStoreId($storeId);
        }

        $attributesToSelect = $this->_getAttributesToSelect();

        $productCollection->addAttributeToSelect($attributesToSelect);

        $productCollection->addAttributeToFilter('entity_id', array('in'  =>  $ids));

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
     * @return array
     */
    protected function _getAttributesToSelect()
    {
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
            'sku'
        );

        if ($this->canUseAttributes()) {
            foreach ($this->getAttributes() as $attr) {
                $action = $attr->getAttributeCode();

                $attributesToSelect[] = $action;
            }
            return $attributesToSelect;
        }
        return $attributesToSelect;
    }
}
