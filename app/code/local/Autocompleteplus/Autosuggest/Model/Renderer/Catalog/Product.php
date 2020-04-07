<?php
/**
 * Products File
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
 * Autocompleteplus_Autosuggest_Model_Renderer_Catalog_Product
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
class Autocompleteplus_Autosuggest_Model_Renderer_Catalog_Product extends
    Autocompleteplus_Autosuggest_Model_Renderer_Abstract
{
    protected $_standardImageFields = array('image', 'small_image', 'thumbnail');
    protected $_useAttributes;
    protected $_attributes;
    protected $_xmlGenerator;
    protected $_categories;
    protected $_configurableAttributes;
    protected $_configurableChildren;
    protected $_configurableChildrenIds;
    protected $_saleable;
    protected $_simpleProductIds;
    protected $_xmlElement;
    protected $_productAttributes;
    protected $_product;
    protected $_rootCategoryId;
    protected $_outputHelper;
    protected $_attributesValuesCache;
    protected $_attributesSetsCache;
    protected $_customersGroups;
    protected $_batchesHelper;
    protected $_catalog_to_date;
    protected $_skip_discounted_products;

    const ISPKEY = 'ISPKEY_';

    /**
     * Autocompleteplus_Autosuggest_Model_Renderer_Catalog_Product constructor.
     */
    public function __construct()
    {
        $this->_attributesValuesCache = array();
        $this->_attributesSetsCache = array();
        $this->_customersGroups = array();
        $customerGroups = Mage::getModel('customer/group')->getCollection();
        $this->_batchesHelper = Mage::helper('autocompleteplus_autosuggest/batches');
        foreach($customerGroups as $type) {
            $this->_customersGroups[$type->getCustomerGroupId()] = $type->getCustomerGroupCode();
        }
    }

    /**
     * SetXmlElement
     *
     * @param Autocompleteplus_Autosuggest_Xml_Generator $xmlGenerator comment
     *
     * @return $this
     */
    public function setXmlElement(&$xmlGenerator)
    {
        $this->_xmlElement = $xmlGenerator;

        return $this;
    }


    public function setSkipDiscountedProducts($val) {
        $this->_skip_discounted_products = $val;
    }

    /**
     * GetXmlElement
     *
     * @return mixed
     */
    public function getXmlElement()
    {
        return $this->_xmlElement;
    }

    /**
     * SetProduct
     *
     * @param Mage_Catalog_Model_Product $product comment
     *
     * @return $this
     */
    public function setProduct($product)
    {
        $this->_product = $product;

        return $this;
    }

    /**
     * GetProduct
     *
     * @return mixed
     */
    public function getProduct()
    {
        return $this->_product;
    }

    /**
     * SetCatalogRuleToDate
     *
     * @param Mage_Catalog_Model_Product $product comment
     *
     * @return $this
     */
    public function setCatalogRuleToDate($to_date)
    {
        $this->_catalog_to_date = $to_date;

        return $this;
    }

    /**
     * GetCatalogRuleToDate
     *
     * @param Mage_Catalog_Model_Product $product comment
     *
     * @return mixed
     */
    public function getCatalogRuleToDate()
    {
        return $this->_catalog_to_date;
    }

    /**
     * GetImageField
     *
     * @return mixed
     */
    public function getImageField()
    {
        if (!$this->_imageField) {
            $this->_imageField = Mage::getStoreConfig(
                'autocompleteplus/config/imagefield'
            );
        }

        return $this->_imageField;
    }

    /**
     * CanUseAttributes
     *
     * @return bool
     */
    public function canUseAttributes()
    {
        if (!$this->_useAttributes) {
            $this->_useAttributes = Mage::getStoreConfigFlag(
                'autocompleteplus/config/attributes'
            );
        }

        return $this->_useAttributes;
    }

    /**
     * GetRootCategoryId
     *
     * @return int
     */
    public function getRootCategoryId()
    {
        if (!$this->_rootCategoryId) {
            $this->_rootCategoryId = Mage::app()
                ->getStore($this->getStoreId())->getRootCategoryId();
        }

        return $this->_rootCategoryId;
    }

    /**
     * GetSaleable
     *
     * @return bool
     */
    public function getSaleable()
    {
        return (bool) $this->_saleable;
    }

    /**
     * GetConfigurableChildren
     *
     * @return mixed
     */
    public function getConfigurableChildren()
    {
        return $this->getProduct()->getTypeInstance()->getUsedProducts();
    }

    /**
     * GetConfigurableChildrenIds
     *
     * @return array
     */
    public function getConfigurableChildrenIds()
    {
        $configurableChildrenIds = array();
        foreach ($this->getConfigurableChildren() as $child) {
            $configurableChildrenIds[] = $child->getId();
            if ($this->getProduct()->isInStock()) {
                if (method_exists($child, 'isSaleable') && !$child->isSaleable()) {
                    // the simple product is probably disabled (because its in stock)
                    continue;
                }
                $this->_saleable = true;
            }
        }

        return $configurableChildrenIds;
    }

    /**
     * GetProductCollection
     *
     * @param bool $new comment
     *
     * @return object
     */
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

    /**
     * GetCategoryMap
     *
     * @return array
     */
    public function getCategoryMap()
    {
        if (!$this->_categories) {
            $categoryMap = array();
            $categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->addAttributeToSelect('name');

            foreach ($categories as $category) {
                $categoryMap[] = new Varien_Object(
                    array(
                        'id' => $category->getId(),
                        'path' => $category->getPath(),
                        'parent_id' => $category->getParentId(),
                        'name' => $category->getName()
                    )
                );
            }
            $this->_categories = $categoryMap;
        }

        return $this->_categories;
    }

    public function getSimpleProductsPriceOfConfigurable()
    {
        $simple_products_price = array();
        $pricesByAttributeValues = array();
        $attributes = $this->getProduct()
            ->getTypeInstance(true)
            ->getConfigurableAttributes($this->getProduct());
        $basePrice = $this->getProduct()->getFinalPrice();
        $items = $attributes->getItems();
        if (is_array($items)) {
            foreach ($items as $attribute) {
                $prices = $attribute->getPrices();
                if (is_array($prices)) {
                    foreach ($prices as $price) {
                        if ($price['is_percent']) {
                            /**
                             * If the price is specified in percents
                             */
                            $pricesByAttributeValues[$price['value_index']]
                                = (float) $price['pricing_value'] * $basePrice / 100;
                        } else {
                            /**
                             * If the price is absolute value
                             */
                            $pricesByAttributeValues[$price['value_index']]
                                = (float) $price['pricing_value'];
                        }
                    }
                }
            }
        }

        foreach ($this->getConfigurableChildren() as $sProduct) {
            $totalPrice = $basePrice;
            foreach ($attributes as $attribute) {
                $value = $sProduct->getData(
                    $attribute->getProductAttribute()->getAttributeCode()
                );
                if (isset($pricesByAttributeValues[$value])) {
                    $totalPrice += $pricesByAttributeValues[$value];
                }
            }
            $simple_products_price[$sProduct->getId()] = $totalPrice;
        }

        return $simple_products_price;
    }

    public function getCategoryPathsByProduct()
    {
        $productCategories = $this->getProduct()->getCategoryIds();
        $rootCategoryId = $this->getRootCategoryId();
        $paths = array();
        $category_names = array();
        $all_categories = $this->getCategoryMap();
        foreach ($all_categories as $category) {
            if (in_array($category['id'], $productCategories)) {
                $path = explode('/', $category['path']);
                //we don't want the root category for the entire site
                array_shift($path);
                if ($rootCategoryId &&
                    is_array($path) &&
                    isset($path[0]) &&
                    $path[0] != $rootCategoryId
                ) {
                    continue;
                }
                //we want more specific categories first
                $paths[] =  implode(':', array_reverse($path));
                $category_names[] = $category['name'];
            }
        }
        return array(array_filter($paths), $category_names);
    }

    /**
     * GetConfigurableAttributes
     *
     * @return array
     */
    public function getConfigurableAttributes()
    {
        // Collect options applicable to the configurable product
        $productAttributeOptions = $this->getProduct()
            ->getTypeInstance()
            ->getConfigurableAttributesAsArray($this->getProduct());
        $configurableAttributes = array();

        foreach ($productAttributeOptions as $productAttribute) {
            $attributeFull = Mage::getModel('eav/config')
                ->getAttribute(
                    'catalog_product',
                    $productAttribute['attribute_code']
                );

            foreach ($productAttribute['values'] as $attribute) {
                $configurableAttributes[$productAttribute['store_label']]['values'][]
                    = $attribute['store_label'];
            }

            $configurableAttributes[$productAttribute['store_label']]
            ['is_filterable'] = $attributeFull['is_filterable'];
            $configurableAttributes[$productAttribute['store_label']]
            ['frontend_input'] = $attributeFull['frontend_input'];
            $configurableAttributes[$productAttribute['store_label']]
            ['attribute_code'] = $attributeFull['attribute_code'];
        }

        return $configurableAttributes;
    }

    /**
     * GetProductAttributes
     *
     * @return mixed
     */
    public function getProductAttributes()
    {
        return $this->getProduct()
            ->getTypeInstance()
            ->getConfigurableAttributes($this->getProduct());
    }

    /**
     * GetPriceRange
     *
     * @return array
     */
    public function getPriceRange($basePrice)
    {
        $pricesByAttributeValues = array();
        $attributes = $this->getProductAttributes();
        $items = $attributes->getItems();
        $min_price = null;
        $max_price = null;
        $totalPrice = false;
        if (is_array($items)) {
            foreach ($items as $attribute) {
                $prices = $attribute->getPrices();
                if (is_array($prices)) {
                    foreach ($prices as $price) {
                        if ($price['is_percent']) {
                            /**
                             * If the price is specified in percents
                             */
                            $pricesByAttributeValues[$price['value_index']]
                                = (float) $price['pricing_value'] * $basePrice / 100;
                        } else {
                            /**
                             * If the price is absolute value
                             */
                            $pricesByAttributeValues[$price['value_index']]
                                = (float) $price['pricing_value'];
                        }
                    }
                }
            }
        }

        $simple = $this->getConfigurableChildren();
        foreach ($simple as $sProduct) {
            $totalPrice = $basePrice;
            foreach ($attributes as $attribute) {
                $value = $sProduct->getData(
                    $attribute->getProductAttribute()->getAttributeCode()
                );
                if (isset($pricesByAttributeValues[$value])) {
                    $totalPrice += $pricesByAttributeValues[$value];
                }
            }
            if (!$min_price || $totalPrice < $min_price) {
                $min_price = $totalPrice;
            }
            if (!$max_price || $totalPrice > $max_price) {
                $max_price = $totalPrice;
            }
        }
        if (is_null($min_price)) {
            $min_price = 0;
        }
        if (is_null($max_price)) {
            $max_price = 0;
        }

        return array(
            'price_min' => $min_price,
            'price_max' => $max_price,
        );
    }

    /**
     * GetSimpleProductParent
     *
     * @return mixed
     */
    public function getSimpleProductParent()
    {
        return Mage::getModel('catalog/product_type_configurable')
            ->getParentIdsByChild($this->getProduct()->getId());
    }

    /**
     * GetOrderCount
     *
     * @return int
     */
    public function getOrderCount()
    {

        $orderData = $this->getOrderData();

        return ($this->getOrderData() != null
            && array_key_exists($this->getProduct()->getId(), $orderData))
            ? $orderData[$this->getProduct()->getId()] : 0;
    }

    /**
     * GetProductResource
     *
     * @return object
     */
    public function getProductResource()
    {
        return Mage::getResourceSingleton('catalog/product');
    }

    /**
     * @TODO Refactor indentation/conditions
     */
    /**
     * RenderProductVariantXml
     *
     * @param mixed $productXmlElem comment
     *
     * @return void
     */
    public function renderProductVariantXml($productXmlElem, $chilren_ids)
    {
        if ($this->canUseAttributes()) {
            if ($this->getProduct()->isConfigurable()
                && count($this->getConfigurableAttributes()) > 0
            ) {
                $variants = array();
                $configurable_simple_skus = array();
                foreach ($this->getConfigurableAttributes()
                         as $attrName => $confAttrN) {
                    if (is_array($confAttrN)
                        && array_key_exists('values', $confAttrN)
                    ) {
                        $variants[] = $confAttrN['attribute_code'];
                        $values = implode(' , ', $confAttrN['values']);
                        $this->getXmlElement()->createChild(
                            'attribute',
                            array(
                                'is_configurable' => 1,
                                'is_filterable' => $confAttrN['is_filterable'],
                                'name' => $attrName,
                            ),
                            $values,
                            $productXmlElem
                        );
                    }
                }

                $simple_products_price = $this
                    ->getSimpleProductsPriceOfConfigurable();

                if (count($variants) > 0) {
                    $variantElem = $this->getXmlElement()
                        ->createChild('variants', false, false, $productXmlElem);

                    $discounted_items = array();
                    if ($this->_skip_discounted_products) {
                        $child_items = Mage::getModel('catalog/product')->getCollection()
                            ->addAttributeToSelect(array_merge($variants,
                                array('price', 'special_price')))
                            ->addAttributeToFilter('entity_id', array('in' => $chilren_ids));
                        foreach ($child_items as $ch_it) {
                            if (floatval($ch_it->getSpecialPrice()) > 0) {
                                $discounted_items[] = $ch_it->getId();
                            }
                        }
                    }


                    foreach ($this->getConfigurableChildren() as $child_product) {
                        if (!in_array(
                            $this->getProduct()->getStoreId(),
                            $child_product->getStoreIds()
                        )) {
                            continue;
                        }

                        if ($child_product->getStockItem()
                            && $child_product->getStockItem()->getIsInStock()) {
                            $is_variant_in_stock = 1;
                        } else {
                            $is_variant_in_stock = 0;
                        }

                        if (method_exists($child_product, 'isSaleable')) {
                            $is_variant_sellable = (
                            $child_product->isSaleable()
                            ) ? 1 : 0;
                        } else {
                            $is_variant_sellable = '';
                        }

                        if ($this->_skip_discounted_products && in_array($child_product->getId(), $discounted_items)) {
                            $is_variant_sellable = '';
                        }

                        if (method_exists($child_product, 'getVisibility')) {
                            $is_variant_visible = (
                            $child_product->getVisibility()
                            ) ? 1 : 0;
                        } else {
                            $is_variant_visible = '';
                        }

                        $variant_price = (array_key_exists(
                            $child_product->getId(),
                            $simple_products_price
                        )) ?
                            $simple_products_price[$child_product->getId()] : '';

                        $variantElementAttributes = array(
                            'id' => $child_product->getId(),
                            'type' => $child_product->getTypeID(),
                            'visibility' => $is_variant_visible,
                            'is_in_stock' => $is_variant_in_stock,
                            'is_seallable' => $is_variant_sellable,
                            'price' => $variant_price
                        );
                        $variantImage = null;
                        if ($child_product->getImage()
                            && $child_product->getImage() != ''
                            && $child_product->getImage() != 'no_selection') {
                            $variantImage = utf8_encode(
                                htmlspecialchars(
                                    (Mage::getModel('catalog/product_media_config')
                                        ->getMediaUrl($child_product->getImage()))
                                )
                            );
                        }

                        if (!$variantImage) {
                            $variantImage = utf8_encode(
                                htmlspecialchars(
                                    (Mage::helper('catalog/image')->init($child_product, 'thumbnail'))
                                )
                            );
                        }

                        if ($variantImage) {
                            $variantElementAttributes['variantimage'] = $variantImage;
                        }
                        $productVariation = $this->getXmlElement()
                            ->createChild(
                                'variant',
                                $variantElementAttributes,
                                false,
                                $variantElem
                            );

                        $this->getXmlElement()->createChild(
                            'name',
                            false,
                            $child_product->getName(),
                            $productVariation
                        );

                        foreach ($variants as $attribute_code) {
                            $attribute = Mage::getSingleton('eav/config')
                                ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attribute_code);
                            if (!$attribute['is_configurable']
                                || (!in_array($attribute['attribute_code'], $variants))
                            ) {
                                continue;
                            }

                            if (!$attribute['store_label'] && !$attribute['frontend_label']) {
                                // skip variant attribute without a name
                                continue;
                            }

                            $variant_name = isset($attribute['store_label'])? $attribute['store_label'] : $attribute['frontend_label'];
                            $this->getXmlElement()->createChild(
                                'variant_attribute',
                                array(
                                    'is_configurable' => 1,
                                    'is_filterable' => $attribute->getIsFilterable(),
                                    'name' => $variant_name,
                                    'name_code' => $attribute->getId(),
                                    'value_code' => $child_product->getData(
                                        $attribute->getAttributeCode()
                                    ),
                                ), utf8_encode(
                                htmlspecialchars(
                                    $attribute->getFrontend()->getValue($child_product)
                                )
                            ), $productVariation
                            );
                        }
                        $configurable_simple_skus[] = $child_product->getSku();
                    }
                    $attributeElem = $this->getXmlElement()->createChild('attribute', array(
                        'is_filterable' => 0,
                        'name' => 'configurable_simple_skus',
                    ), false, $productXmlElem);
                    $this->getXmlElement()->createChild('attribute_values', false,
                        implode(',', $configurable_simple_skus), $attributeElem);
                    $this->getXmlElement()->createChild('attribute_label', false,
                        'configurable_simple_skus', $attributeElem);
                }
            }
        }
    }

    protected function renderTieredPrices($product, $productXmlElem) {
        $tieredPrices = Mage::getResourceModel('catalog/product_attribute_backend_tierprice')
            ->loadPriceData(
                $product->getID(),
                Mage::app()->getWebsite()->getId()
            );

        if (count($tieredPrices) > 0) {
            $tieredPricesElem = $this->getXmlElement()
                ->createChild(
                    'tiered_prices',
                    false,
                    false,
                    $productXmlElem
                );

            foreach ($tieredPrices as $trP) {
                $this->getXmlElement()
                    ->createChild(
                        'tiered_price',
                        array(
                            'cust_group' => array_key_exists($trP['cust_group'], $this->_customersGroups) ?
                                $this->_customersGroups[$trP['cust_group']] : $trP['cust_group'],
                            'cust_group_id' => $trP['cust_group'],
                            'price' => $trP['price'],
                            'min_qty' => $trP['price_qty']
                        ),
                        false,
                        $tieredPricesElem
                    );
            }

        } elseif (count($this->getProduct()->getData('group_price')) > 0) {
            $tieredPricesElem = $this->getXmlElement()
                ->createChild(
                    'tiered_prices',
                    false,
                    false,
                    $productXmlElem
                );

            foreach ($this->getProduct()->getData('group_price') as $trP) {
                $this->getXmlElement()
                    ->createChild(
                        'tiered_price',
                        array(
                            'cust_group' => array_key_exists($trP['cust_group'], $this->_customersGroups) ?
                                $this->_customersGroups[$trP['cust_group']] : $trP['cust_group'],
                            'cust_group_id' => $trP['cust_group'],
                            'price' => $trP['price'],
                            'min_qty' => 1
                        ),
                        false,
                        $tieredPricesElem
                    );
            }
        }
    }

    /**
     * RenderProductAttributeXml
     *
     * @param mixed $attr           comment
     * @param mixed $productXmlElem comment
     *
     * @return void
     */
    public function renderProductAttributeXml($attr, $productXmlElem)
    {
        if ($this->canUseAttributes()) {
            $action = $attr->getAttributeCode();

            $attrValue = $this->getProduct()->getData($action);

            if (!array_key_exists($action, $this->_attributesValuesCache)) {
                $this->_attributesValuesCache[$action] = array();
            }

            $is_filterable = $attr->getIsFilterable();
            $attribute_label = $attr->getFrontendLabel();
            $_helper = $this->_getOutputHelper();

            try {
                switch ($attr->getFrontendInput()) {
                    case 'select':
                        if (method_exists($this->getProduct(), 'getAttributeText')) {
                            /**
                             * We generate key for cached attributes array
                             * we make it as string to avoid null to be a key
                             */
                            $attrValidKey = $attrValue != null ? self::ISPKEY.$attrValue : self::ISPKEY;

                            if (!array_key_exists($attrValidKey, $this->_attributesValuesCache[$action])) {
                                $attrValueText = $_helper->productAttribute(
                                    $this->getProduct(),
                                    $this->getProduct()->getAttributeText($action),
                                    $action
                                );

                                $this->_attributesValuesCache[$action][$attrValidKey] = $attrValueText;
                                $attrValue = $attrValueText;
                            } else {
                                $attrValueText = $this->_attributesValuesCache[$action][$attrValidKey];

                                $attrValue = $attrValueText;
                            }
                        }

                        break;
                    case 'textarea':
                    case 'price':
                    case 'text':
                        break;
                    case 'multiselect':
                        $attrValue = $this->getProduct()->getResource()
                            ->getAttribute($action)->getFrontend()->getValue($this->getProduct());
                        break;
                    default:
                        $attrValue = null;
                        break;
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'autocomplete.log', true);
            }

            if ($attrValue) {
                $attributeElem = $this->getXmlElement()->createChild('attribute', array(
                    'is_filterable' => $is_filterable,
                    'name' => $attr->getAttributeCode(),
                ), false, $productXmlElem);

                $this->getXmlElement()->createChild('attribute_values', false,
                    $attrValue, $attributeElem);
                $this->getXmlElement()->createChild('attribute_label', false,
                    $attribute_label, $attributeElem);
            }
        }
    }

    public function renderXml()
    {
        Mage::getSingleton('core/session')->setRunningProductId($this->getProduct()->getID());

        $categories_data = $this->getCategoryPathsByProduct();

        $saleable = 0;

        try {
            if ($this->getProduct()->getData('isp_sellable') == 1) {
                $saleable = 1;
            } else {
                $saleable = $this->getProduct()->isSalable() ? 1 : 0;
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'autocomplete.log', true);
        }

        $calculatedFinalPrice = $this->getProduct()
            ->getPriceModel()
            ->getFinalPrice(1, $this->getProduct());

        if ($this->getProduct()->isConfigurable()) {
            $priceRange = $this->getPriceRange($calculatedFinalPrice);
        } elseif ($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $priceRange = array('price_min' => 0, 'price_max' => 0);
        } elseif ($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $priceRange = $this->getBundlePriceRange($this->getProduct());
        } else {
            $priceRange = array('price_min' => 0, 'price_max' => 0);
        }

        $url = Mage::helper('catalog/product')->getProductUrl($this->getProduct());
        $nowDateGmt = intval(Mage::getSingleton('core/date')->gmtTimestamp());
        $regularPrice = 0;
        if ($this->getProduct()->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $specialFromDate = $this->getProduct()->getSpecialFromDate();
            $specialToDate = $this->getProduct()->getSpecialToDate();
            $specialPrice = $this->getProduct()->getSpecialPrice();
            $regularPrice = $this->getProduct()->getPrice();
            if (!is_null($specialPrice) && $specialPrice != false && $this->getAction() != 'getbyid') {
                $this->scheduleDistantUpdate($specialFromDate, $specialToDate, $nowDateGmt);
            }
        } else {
            $calculatedFinalPrice = $priceRange['price_min'];
        }

        if ($this->getCatalogRuleToDate() && $this->getAction() != 'getbyid') {
            $ruleDt = new DateTime();
            $ruleDt->setTimestamp($this->getCatalogRuleToDate());
            $ruleDt->setTimezone(new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
            $this->scheduleDistantUpdate(null, $ruleDt->format('Y-m-d H:i:s'), $nowDateGmt);
        }

        if ($url == null || $url == '') {
            $url = Mage::helper('catalog/product')->getProductUrl($this->getProduct()->getId());
        }

        if ($this->getUpdateDate() && $this->getUpdateDate() > $nowDateGmt) {
            $lastModifiedDate = strtotime(
                (string) $this->getProduct()->getUpdatedAt()
            );
        } else {
            $lastModifiedDate = $this->getUpdateDate();
        }

        $thumb = null;
        $base_image = null;

        if ($this->getImageField() != null && $this->getImageField() != '') {
            $thumb = $this->getProduct()->getData($this->getImageField());
            $base_image = $this->getProduct()->getData($this->getImageField());
        }

        if ($thumb == null || $thumb == '' || substr( $thumb, 0, 4 ) !== "http") {
            $thumb = utf8_encode(htmlspecialchars((Mage::helper('catalog/image')->init($this->getProduct(), 'thumbnail'))));
        }

        if ($base_image == null || $base_image == '' || substr( $base_image, 0, 4 ) !== "http") {
            $base_image_url = $this->getProduct()->getImage() ?
                $this->getProduct()->getImage() : $this->getProduct()->getSmallImage();
            $base_image = utf8_encode(
                htmlspecialchars(
                    (Mage::getModel('catalog/product_media_config')->getMediaUrl($base_image_url))
                )
            );
        }
        //Mageworx starting_at price support
        $display_price = $this->getProduct()->getData('display_price');
        if ($display_price && ($calculatedFinalPrice == 0 || !$calculatedFinalPrice)) {
            $calculatedFinalPrice = $display_price;
        }

        if ($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
            && $priceRange['price_min'] < $calculatedFinalPrice
            && $priceRange['price_min'] != 0) {
            $priceRange['price_min'] = $calculatedFinalPrice;
            if ($priceRange['price_min'] > $priceRange['price_max']) {
                $priceRange['price_max'] = $priceRange['price_min'];
            }
        }

        if ($this->getDbvisibility()) {
            $visibility = $this->getVisibilityFromDb($this->getProduct()->getId());
        } else {
            $visibility = $this->getProduct()->getVisibility();
        }
        $xmlAttributes = array(
            'price_min' => ($priceRange['price_min']),
            'price_max' => ($priceRange['price_max']),
            'store' => ($this->getStoreId()),
            'store_id' => ($this->getStoreId()),
            'storeid' => ($this->getStoreId()),
            'id' => ($this->getProduct()->getId()),
            'type' => ($this->getProduct()->getTypeId()),
            'currency' => ($this->getCurrency()),
            'visibility' => $visibility,
            'price' => $calculatedFinalPrice,
            'url' => $url,
            'thumbs' => $thumb,
            'base_image' => $base_image,
            'selleable' => ($saleable),
            'action' => ($this->getAction()),
            'last_updated' => ($this->getProduct()->getUpdatedAt()),
            'updatedate' => ($lastModifiedDate),
            'get_by_id_status' => intval($this->getGetByIdStatus()),
        );
        if ($calculatedFinalPrice < $regularPrice) {
            $xmlAttributes['price_compare_at_price'] = $regularPrice;
        }

        $productElement = $this->getXmlElement()->createChild('product', $xmlAttributes);

        $productRating = $this->getProduct()->getRatingSummary();
        $this->getXmlElement()->createChild('description', false,
            $this->getProduct()->getDescription(), $productElement);
        $this->getXmlElement()->createChild('short', false,
            $this->getProduct()->getShortDescription(), $productElement);
        $this->getXmlElement()->createChild('name', false,
            $this->getProduct()->getName(), $productElement);
        $this->getXmlElement()->createChild('sku', false,
            $this->getProduct()->getSku(), $productElement);

        $this->getXmlElement()->createChild('url_additional', false,
            $this->_getAdditionalProductUrl(), $productElement);

        if ($productRating) {
            $this->getXmlElement()->createChild('review', false, $productRating->getRatingSummary(),
                $productElement);
            $this->getXmlElement()->createChild('reviews_count', false,
                $productRating->getReviewsCount(), $productElement);
        }

        $newFromDate = $this->getProduct()->getNewsFromDate();
        $newToDate = $this->getProduct()->getNewsToDate();
        if ($newFromDate) {
            $this->getXmlElement()->createChild('newfrom', false,
                Mage::getModel('core/date')->timestamp($newFromDate), $productElement);
            if ($newToDate) {
                $this->getXmlElement()->createChild('newto', false,
                    Mage::getModel('core/date')->timestamp($newToDate), $productElement);
            }
        }

        $this->getXmlElement()->createChild('purchase_popularity', false,
            $this->getOrderCount(), $productElement);
        $this->getXmlElement()->createChild('product_status', false,
            (($this->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) ? 1 : 0), $productElement);

        $source_creation_date = $this->getProduct()->getNewsFromDate();
        $source_creation_date = !$source_creation_date ? $this->getProduct()->getCreatedAt() : $source_creation_date;
        $this->getXmlElement()->createChild('creation_date', false,
            Mage::getModel('core/date')->timestamp($source_creation_date), $productElement);

        $this->getXmlElement()->createChild('updated_date', false,
            Mage::getModel('core/date')->timestamp($this->getProduct()->getUpdatedAt()), $productElement);

        if ($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $this->getXmlElement()->createChild('product_parents', false,
                implode(',', $this->getSimpleProductParent()), $productElement);
        }

        if ($this->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $children_ids = $this->getConfigurableChildrenIds();
            $this->getXmlElement()->createChild('simpleproducts', false,
                implode(',', $children_ids), $productElement);
            $this->renderProductVariantXml($productElement, $children_ids);
        }

        if ($this->canUseAttributes()) {
            $attributeSetId = $this->getProduct()->getAttributeSetId();

            if (!array_key_exists($attributeSetId, $this->_attributesSetsCache)) {
                $this->_attributesSetsCache[$attributeSetId] = array();
                $setAttributes = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);

                foreach ($setAttributes as $attrFromSet) {
                    $this->_attributesSetsCache[$attributeSetId][] = $attrFromSet['code'];
                }
            }

            foreach ($this->getAttributes() as $attr) {
                if (in_array($attr->getAttributeCode(), $this->_attributesSetsCache[$attributeSetId])) {
                    $this->renderProductAttributeXml($attr, $productElement);
                }
            }
        }

        $this->renderTieredPrices($this->getProduct(), $productElement);

        $this->getXmlElement()->createChild('categories', false,
            implode(';', $categories_data[0]), $productElement);

        $attributeElem = $this->getXmlElement()->createChild('attribute', array(
            'is_filterable' => 0,
            'name' => 'category_names',
        ), false, $productElement);

        $this->getXmlElement()->createChild('attribute_values', false,
            implode(',', $categories_data[1]), $attributeElem);
        $this->getXmlElement()->createChild('attribute_label', false,
            'category_names', $attributeElem);

        $this->getXmlElement()->createChild('meta_keywords', false,
            $this->getProduct()->getMetaKeyword(), $productElement);
        $this->getXmlElement()->createChild('meta_title', false,
            $this->getProduct()->getMetaTitle(), $productElement);
        $this->getXmlElement()->createChild('meta_description', false,
            $this->getProduct()->getMetaDescription(), $productElement);

        if ($this->getProduct()->getStoreIds()) {
            $this->getXmlElement()->createChild('assigned_to_store_ids', false,
                implode(',', $this->getProduct()->getStoreIds()), $productElement);
        }
    }

    protected function getBundlePriceRange($product) {

        list($_minimalPriceTax, $_maximalPriceTax) = $product->getPriceModel()->getTotalPrices($product);

        return array(
            'price_min' => $_minimalPriceTax,
            'price_max' => $_maximalPriceTax
        );
    }

    protected function _getOutputHelper()
    {
        if ($this->_outputHelper == null) {
            $this->_outputHelper = Mage::helper('catalog/output');
        }

        return $this->_outputHelper;
    }

    public function _getAdditionalProductUrl()
    {
        $is_get_url_path_supported = true;
        if (method_exists('Mage', 'getVersionInfo')) {
            /**
             * GetUrlPath is not supported on EE 1.13... & 1.14...
             */
            $edition_info = Mage::getVersionInfo();
            if ($edition_info['major'] == 1 && $edition_info['minor'] >= 13) {
                $is_get_url_path_supported = false;
            }
        }

        if (method_exists($this->getProduct(), 'getUrlPath') && $is_get_url_path_supported) {
            $product_url = $this->getProduct()->getUrlPath();
            if ($product_url != '') {
                $product_url = Mage::getUrl($product_url);

                return $product_url;
            }
        }

        return '';
    }

    /**
     * @param $specialFromDate
     * @param $specialToDate
     */
    private function scheduleDistantUpdate($specialFromDate, $specialToDate, $nowDateGmt)
    {
        $specialFromDateGmt = null;
        if ($specialFromDate != null) {
            $localDate = new DateTime($specialFromDate, new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
            $specialFromDateGmt = $localDate->getTimestamp();
        }
        if ($specialFromDateGmt && $specialFromDateGmt > $nowDateGmt) {
            $this->_batchesHelper->writeProductUpdate(
                $this->getProduct()->getId(),
                $specialFromDateGmt,
                $this->getProduct()->getSku(),
                $this->_batchesHelper->get_parent_products_ids($this->getProduct())
            );
        } else if ($specialToDate != null) {
            $localDate = new DateTime($specialToDate, new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
            $hour = $localDate->format('H');
            $mins = $localDate->format('i');
            if ($hour == '00' && $mins == '00') {
                $localDate->modify('+86700 seconds'); //make "to" limit inclusive and another 5 minutes for safety
            }
            $specialToDateGmt = $localDate->getTimestamp();
            if ($specialToDateGmt > $nowDateGmt) {
                $this->_batchesHelper->writeProductUpdate(
                    $this->getProduct()->getId(),
                    $specialToDateGmt,
                    $this->getProduct()->getSku(),
                    $this->_batchesHelper->get_parent_products_ids($this->getProduct())
                );
            }
        }
    }

    private function getVisibilityFromDb($product_id)
    {
        //Mage::getModel('core/config')->saveConfig('autocompleteplus_autosuggest/config/db_visibility', 1);
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $eav_table_name = $resource->getTableName('eav_attribute');
        $entity_int_table_name = $resource->getTableName('catalog_product_entity_int');
//        $sql = "SELECT *  FROM `catalog_product_entity_int` WHERE `attribute_id` = 102 AND `entity_id` = 404";
        $query = "select";
        $query .= sprintf(" `%s`.`attribute_id` AS `attribute_id`,", $eav_table_name);
        $query .= sprintf(" `%s`.`entity_id` AS `entity_id`,", $entity_int_table_name);
        $query .= sprintf(" `%s`.`value` AS `value`", $entity_int_table_name);
        $query .= " from";
        $query .= sprintf(" `%s`", $eav_table_name);
        $query .= sprintf(" join `%s` on `%s`.`attribute_id` = `%s`.`attribute_id`", $entity_int_table_name, $eav_table_name, $entity_int_table_name);
        $query .= " where";
        $query .= sprintf(" (`%s`.`attribute_code` = 'visibility')", $eav_table_name);

        $query .= sprintf(" and (`%s`.`entity_id` = :product_id)", $entity_int_table_name);
        $product_id_param = new Varien_Db_Statement_Parameter($product_id);
        $product_id_param->setDataType(PDO::PARAM_INT);
        $params['product_id'] = $product_id_param;

        $result = $readConnection->fetchRow($query, $params);
        return $result['value'];

    }
}
