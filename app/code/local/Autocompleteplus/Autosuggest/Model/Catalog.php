<?php

class Autocompleteplus_Autosuggest_Model_Catalog extends Mage_Core_Model_Abstract{
    private $imageField;
    private $standardImageFields;
    private $useAttributes;
    private $attributes;
    private $currency;
    private $pageNum;

    public function renderCatalogXml($startInd, $count, $storeId='', $orders='', $month_interval='', $checksum=''){

        $this->_initCatalogCommonFields($storeId);

        if(!$startInd){
            $startInd=0;
        }

        //maxim products on one page is 10000
        if(!$count||$count>10000){
            $count=10000;
        }
        //retrieving page number
        $this->pageNum=floor(($startInd/$count));

        $mage=Mage::getVersion();
        $ext=Mage::helper('autocompleteplus_autosuggest')->getVersion();

        $xml='<?xml version="1.0"?>';
        $xml.='<catalog version="'.$ext.'" magento="'.$mage.'">';

        $collection=Mage::getModel('catalog/product')->getCollection();
        if(isset($storeId)&& $storeId!=''){
            $collection->addStoreFilter($storeId);
            $store_id = $storeId;
        } else {
            $store_id = Mage::app()->getStore()->getStoreId();
        }

        //setting page+products on the page
        $collection->getSelect()->limit($count,$startInd);//->limitPage($pageNum, $count);//setPage($pageNum, $count)->load();
        $collection->load();

        // number of orderes per product section
        if (isset($orders) && $orders == '1'){
            $product_id_list = array();
            foreach ($collection as $product){
                $product_id_list[] = $product->getId();
            }

            if(isset($month_interval)&& $month_interval!=''){
                $month_interval = $month_interval;
            } else {
                $month_interval = 12;
            }
            $orders_per_product = $this->_getOrdersPerProduct($store_id, $product_id_list, $month_interval);
        } else {// end - number of orderes per product section
            $orders_per_product = null;
        }
        
        $this->_root_category_id = Mage::app()->getStore($store_id)->getRootCategoryId();

        if(isset($checksum) && $checksum != ''){
            $is_checksum = $checksum;
            $helper = Mage::helper('autocompleteplus_autosuggest');
            $_tableprefix = (string)Mage::getConfig()->getTablePrefix();
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        } else {
            $is_checksum = 0;
            $helper = null;
            $_tableprefix = null;
            $write = null;
            $read = null;
        }

        foreach ($collection as $product) {
            $productCollData=$product->getData();
            try{
                $productModel=Mage::getModel('catalog/product')
                    ->setStore($storeId)->setStoreId($storeId)
                    ->load($productCollData['entity_id']);
            } catch (Exception $e){
				continue;
			}
            $prodId           =$productModel->getId();
            $sku         =$productModel->getSku();
            $row=$this->renderProductXmlRow($productModel,$orders_per_product);
            $xml.=$row;
            if ($is_checksum && $helper){
                if ($helper->isChecksumTableExists()){
                    $checksum = $helper->calculateChecksum($productModel);
                    $helper->updateSavedProductChecksum($_tableprefix, $read, $write, $prodId, $sku, $store_id, $checksum);
                }
            }
        }
        $xml.='</catalog>';
        return $xml;
    }

    public function renderUpdatesCatalogXml($count,$from,$to,$storeId){
        $storeQ='';

        if($storeId!=''){
            $storeQ   = 'AND store_id='.$storeId;
        }else{
            $storeId = Mage::app()->getStore()->getStoreId();
        }
        $this->_initCatalogCommonFields($storeId);
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();
        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_batches` WHERE update_date BETWEEN ? AND ? '.$storeQ. ' order by update_date' . ' LIMIT '.$count;
        $updates=$read->fetchAll($sql,array($from,$to));

        $mage=Mage::getVersion();
        $ext=Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $this->_root_category_id = Mage::app()->getStore($storeId)->getRootCategoryId();        

        $xml='<?xml version="1.0"?>';
        $xml.='<catalog fromdatetime="'.$from.'" version="'.$ext.'" magento="'.$mage.'">';
        foreach ($updates as $batch) {
            if($batch['action']=='update'){
                $productId =         $batch['product_id'];
                $sku =               $batch['sku'];
                $batchStoreId =           $batch['store_id'];

                if($storeId!=$batchStoreId){
                    $this->currency = Mage::app()->getStore($batchStoreId)->getCurrentCurrencyCode();
                }

                $productModel = null;
                
                if($productId!=null){
//                  load product by id
                    try{
                        $productModel=Mage::getModel('catalog/product')
                            ->setStoreId($batchStoreId)
                            ->load($productId);
                    }catch (Exception $e){
	                    $batch['action'] = 'remove';
                        $xml.=$this->_makeRemoveRow($batch);
                        continue;
        			}
                }else{
                    // product not found - changing action to remove
                    $batch['action'] = 'remove';
                    $xml.=$this->_makeRemoveRow($batch);
                    continue;
                    
                    /*
                     * FIX - Fatal error: Call to undefined method Mage_Catalog_Model_Resource_Product_Flat::loadAllAttributes()
                     */
//                     $productModel=Mage::getModel('catalog/product')
//                         ->loadByAttribute('sku', $sku)
//                         ->setStoreId($batchStoreId);
                }

                if($productModel==null){
                    continue;
                }

                $updatedate =        $batch['update_date'];
                $action =            $batch['action'];
                $xmlAttrs='action="'.$action.'"  updatedate="'.$updatedate.'" storeid="'.$storeId.'"' ;
//                 $xmlAttrs='action="'.$action.'"  updatedate="'.$updatedate.'"';
                $xml.=$this->renderProductXmlRow($productModel,null,$xmlAttrs);
            }else{
                $xml.=$this->_makeRemoveRow($batch);
            }

        }
        $xml.='</catalog>';
        return $xml;
    }

    public function renderCatalogFromIds($count,$fromId,$storeId){

        $this->_initCatalogCommonFields($storeId);

        $mage = Mage::getVersion();
        $ext  = Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $xml  = '<catalog version="'.$ext.'" magento="'.$mage.'">';

        $_productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($storeId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('from' => $fromId));
        
        $_productCollection->getSelect()->limit($count);

        //add media gallery to collection

        $action= 'getfromid';

        foreach($_productCollection as $product){
            if($product){
                $id = $product->getId();
                $lastUpdateddt = $product->getUpdatedAt();

                $xmlAttrTemplate = 'last_updated="%s" get_by_id_status="1" action="%s" storeid="%s"';
                $xmlAttrs = sprintf($xmlAttrTemplate,
                    $lastUpdateddt,
                    $action,
                    $storeId
                );

                $xml .= $this->renderProductXmlRow($product, null, $xmlAttrs);

            } else {
                $xml .= '<product action="' . $action . '" product="' . $id . '" get_by_id_status="0"></product>';
            }

        }

        $xml.='</catalog>';

        return $xml;
    }

    /**
     * Creates an XML representation of catalog by ids
     * @param  array  $ids     
     * @param  integer $storeId 
     * @return string
     */
    public function renderCatalogByIds($ids, $storeId = 0)
    {

        $this->_initCatalogCommonFields($storeId);

        $mage = Mage::getVersion();
        $ext = Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $xml = '<catalog version="'.$ext.'" magento="'.$mage.'">';

        $_productCollection = Mage::getModel('catalog/product')->getCollection()
            //->addStoreFilter($storeId)
            //->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array(
                'in' => $ids
            ));
        
            $this->_root_category_id = Mage::app()->getStore($storeId)->getRootCategoryId();

        $action= 'getbyid';

        foreach($ids as $id){

            $productModel=Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->load($id);

            if($productModel->getId()){

                $lastUpdateddt=$productModel->getUpdatedAt();

                $xmlAttrs='last_updated="'.$lastUpdateddt.'" get_by_id_status="1" action="'.$action.'"  storeid="'.$storeId.'"' ;

                $xml.=$this->renderProductXmlRow($productModel,null,$xmlAttrs);

            }else{
                $xml.='<product action="'.$action.'" product="'.$id.'" get_by_id_status="0"></product>';
            }

        }

        $xml.='</catalog>';

        return $xml;
    }

    public function getCategoryMap()
    {
        if(!$this->_categories){
            $categoryMap = array();
            $categories = Mage::getModel('catalog/category')->getCollection()->load();

            foreach($categories as $category){
                $categoryMap[] = new Varien_Object(array(
                    'id'=>$category->getId(),
                    'path'=>$category->getPath(),
                    'parent_id'=>$category->getParentId()
                ));
            }

            $this->_categories = $categoryMap;
        }

        return $this->_categories;
    }

    public function getCategoryPathsByProduct(Mage_Catalog_Model_Product $product)
    {
        $productCategories = $product->getCategoryIds();
		$root_category_id = $this->_root_category_id;
        $paths = array_map(function($category) use ($productCategories, $root_category_id) {
            if(in_array($category->getId(), $productCategories)){
                $path = explode('/', $category->getPath());
                //we don't want the root category for the entire site
                array_shift($path);
                if ($root_category_id && is_array($path) && $path[0] != $root_category_id){
                    return array();
                }
                //we want more specific categories first
                return implode(':', array_reverse($path));
            }
        }, $this->getCategoryMap());

        return array_filter($paths);
    }

    public function renderProductXmlRow($productModel,$orders_per_product,$xmlAttrs='action="insert"')
    {
        $helper       = Mage::helper('autocompleteplus_autosuggest');
        $categories   = $this->getCategoryPathsByProduct($productModel);
        $price        = $this->_getPrice($productModel);
        $sku          = $productModel->getSku();
        $stock_status = $productModel->isInStock();
        $stockItem    = $productModel->getStockItem();

        if($stockItem){
            if($stockItem->getIsInStock() && $stock_status){
                $sell=1;
            }else{
                $sell=0;
            }
        }else{
            if($stock_status){
                $sell=1;
            }else{
                $sell=0;
            }
        }

        $productUrl = '';       // getting the product's url according to the store_id
        
        $is_getUrlPath_supported = true;
        if (method_exists('Mage' , 'getVersionInfo')){  // getUrlPath is not supported on EE 1.13... & 1.14...
            $edition_info = Mage::getVersionInfo();
            if ($edition_info['major'] == 1 && $edition_info['minor'] >= 13){
                $is_getUrlPath_supported = false;
            }
        }
        
        if (method_exists($productModel, 'getUrlPath') && $is_getUrlPath_supported){
            $productUrl = $productModel->getUrlPath();
            if ($productUrl != ''){
                $productUrl = Mage::getUrl($productUrl);
            }
        }
        if ($productUrl == '' && method_exists($productModel, 'getProductUrl')){
            $productUrl = $productModel->getProductUrl();
//             $pattern = '/\?___.*/';
//             $productUrl = preg_replace($pattern, '', $productUrl);
        }
        if ($productUrl == '') {
            $productUrl = Mage::helper('catalog/product')->getProductUrl($productModel->getId());
        }

        $prodId        = $productModel->getId();
        $prodDesc      = $productModel->getDescription();
        $prodShortDesc = $productModel->getShortDescription();
        $prodName      = $productModel->getName();
        $visibility    = $productModel->getVisibility();

        if(defined('Mage_Catalog_Model_Product_Status::STATUS_ENABLED')){
            if ($productModel->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED){
                $product_status = 1;
            } else {
                $product_status = 0;
            }
        } else {
            if ($productModel->getStatus() == 1){
                $product_status = 1;
            } else {
                $product_status = 0;
            }
        }

        try{
            if(in_array($this->imageField,$this->standardImageFields)){
                $prodImage   =Mage::helper('catalog/image')->init($productModel, $this->imageField);
            }else{
                $function='get'.$this->imageField;
                $prodImage  =$productModel->$function();
            }

            try{
                $product_media_config = Mage::getModel('catalog/product_media_config');
                $product_base_image = $product_media_config->getMediaUrl($productModel->getImage());
            } catch (Exception $e){
                $product_base_image = '';
            }

        }catch(Exception $e){
            $prodImage='';
            $product_base_image = '';
        }
        
        $grouped_children_ids = array();
        if($productModel->getTypeID()=='configurable'){
            $configurableAttributes=$this->_getConfigurableAttributes($productModel);

            $configurableChildren = $this->_getConfigurableChildren($productModel);
            
            // getting all children's ids
            $configurable_children_ids = array();
            foreach($configurableChildren as $child_product){
                $configurable_children_ids[] = $child_product->getId();
            }
            
            // getting sellable option for the configurable product
            if ($sell){     // Configurable is in stock
                $is_in_stock_child_exist = false; 
                foreach($configurableChildren as $child_product){
                    if ($child_product->getStockItem()->getIsInStock()){
                        if (method_exists($child_product, 'isSaleable') && !$child_product->isSaleable()){
                            // the simple product is probably disabled (because its in stock)
                            continue;
                        }
                        $is_in_stock_child_exist = true;
                        break;
                    }
                }
                if (!$is_in_stock_child_exist){
                    # Configurable is in stock, but has no in stock children
                    $sell = 0;
                }
            }            
            
            try{
                $priceRange=$this->_getPriceRange($productModel);
            }catch(Exception $e){
                $priceRange='price_min="" price_max=""';
            }
        } else if ($productModel->getTypeID() == 'grouped'){
            $grouped_children = $this->_getGroupedChildren($productModel);
            foreach($grouped_children as $child_product){
                $grouped_children_ids[] = $child_product->getId();
            }
            // getting sellable option for the grouped product
            if ($sell){     // Grouped is in stock
                $is_in_stock_child_exist = false;
                foreach($grouped_children as $child_product){
                    if ($child_product->getStockItem()->getIsInStock()){
                        if (method_exists($child_product, 'isSaleable') && !$child_product->isSaleable()){
                            continue;
                        }
                        $is_in_stock_child_exist = true;
                        break;
                    }
                }  
                if (!$is_in_stock_child_exist){
                    # Configurable is in stock, but has no in stock children
                    $sell = 0;
                }                
            }
            $priceRange='price_min="" price_max=""';
        } else if ($productModel->getTypeID() == 'simple'){
            $simple_product_parents = $this->_getSimpleProductParent($productModel);
            $priceRange='price_min="" price_max=""';
        }else{
            $priceRange='price_min="" price_max=""';
        }

        $num_of_orders = ($orders_per_product != null && array_key_exists($prodId, $orders_per_product)) ? $orders_per_product[$prodId] : 0;
        $row='<product '.$priceRange.'  id="'.$prodId.'" type="'.$productModel->getTypeID().'" currency="'.$this->currency.'" visibility="'.$visibility.'" price="'.$price.'" url="'.$productUrl.'" thumbs="'.$prodImage.'" base_image="'.$product_base_image.'" selleable="'.$sell.'" '.$xmlAttrs.' >';
        $row.='<description><![CDATA['.$prodDesc.']]></description>';
        $row.='<short><![CDATA['.$prodShortDesc.']]></short>';
        $row.='<name><![CDATA['.$prodName.']]></name>';
        $row.='<sku><![CDATA['.$sku.']]></sku>';  
        
        $summaryData = Mage::getModel('review/review_summary')
                        ->setStoreId($productModel->getStoreId())
                        ->load($prodId);
        if (($summaryData['rating_summary'] || $summaryData['rating_summary'] == 0) && $summaryData['rating_summary'] != ''){
            $row.='<review><![CDATA['.$summaryData['rating_summary'].']]></review>';
            $row.='<reviews_count><![CDATA['.$summaryData['reviews_count'].']]></reviews_count>';
        }
        
        $new_from_date = $productModel->getNewsFromDate();
        $new_to_date = $productModel->getNewsToDate();
        if ($new_from_date){
            $row.='<newfrom><![CDATA['.Mage::getModel('core/date')->timestamp($new_from_date).']]></newfrom>';
            if ($new_to_date){
                $row.='<newto><![CDATA['.Mage::getModel('core/date')->timestamp($new_to_date).']]></newto>';
            }
        }
        
        $row.= '<purchase_popularity><![CDATA['.$num_of_orders.']]></purchase_popularity>';
        $row.='<product_status><![CDATA['.$product_status.']]></product_status>';
    
        try{
            $row.='<creation_date><![CDATA['.Mage::getModel('core/date')->timestamp($productModel->getCreatedAt()).']]></creation_date>';
            $row.='<updated_date><![CDATA['.Mage::getModel('core/date')->timestamp($productModel->getUpdatedAt()).']]></updated_date>';
        } catch(Exception $e){
        } 

        if($this->useAttributes!='0'){
            foreach($this->attributes as $attr){
                $action=$attr->getAttributeCode();
                $is_filterable=$attr->getis_filterable();
                $attribute_label = $attr->getStoreLabel($productModel->getStoreId());
                
                if($attr->getfrontend_input()=='select'){
                    if($productModel->getData($action)){
                        if (method_exists($productModel, 'getAttributeText')){
							try{
								$row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
											<attribute_values><![CDATA['.$productModel->getAttributeText($action).']]></attribute_values>
											<attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
									   </attribute>';
							} catch(Exception $e){
								$row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
											<attribute_values><![CDATA['.$productModel->getData($action).']]></attribute_values>
											<attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
										</attribute>';
							} 
                        } else {
                            $row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
                                    <attribute_values><![CDATA['.$productModel->getData($action).']]></attribute_values>
                                    <attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
                                   </attribute>';
                        }
                    }
                }elseif($attr->getfrontend_input()=='textarea'){
                    if($productModel->getData($action)){
                        $row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
                                <attribute_values><![CDATA['.$productModel->getData($action).']]></attribute_values>
                                <attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
                               </attribute>';                    
                    }
                }elseif($attr->getfrontend_input()=='price'){
                    if($productModel->getData($action)){
                        $row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
                                <attribute_values><![CDATA['.$productModel->getData($action).']]></attribute_values>
                                <attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
                               </attribute>';                     
                    }
                }elseif($attr->getfrontend_input()=='text'){
                    if($productModel->getData($action)){
                        $row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
                                <attribute_values><![CDATA['.$productModel->getData($action).']]></attribute_values>
                                <attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
                               </attribute>';                     
                    }
                }elseif($attr->getfrontend_input()=='multiselect'){
                    if($productModel->getData($action)){
                        $values=$productModel->getResource()->getAttribute($action)->getFrontend()->getValue($productModel);
                        $row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'">
                                <attribute_values><![CDATA['.$values.']]></attribute_values>
                                <attribute_label><![CDATA['.$attribute_label.']]></attribute_label>
                               </attribute>';                     
                    }
                }
            }
            
            if($productModel->getTypeID()=='configurable' && count($configurableAttributes)>0){
                $configural_attributes = array();
                foreach($configurableAttributes as $attrName=>$confAttrN){
                    if(is_array($confAttrN) && array_key_exists('values',$confAttrN)){
                        $configural_attributes[] = $attrName;
                        $values=implode(' , ',$confAttrN['values']);
                        $row.='<attribute is_configurable="1" is_filterable="'.$confAttrN['is_filterable'].'" name="'.$attrName.'"><![CDATA['.$values.']]></attribute>';
                    }
                }
                $simple_products_price = $this->_getSimpleProductsPriceOfConfigurable($productModel, $configurableChildren);

                if (!empty($configural_attributes)){
                    $product_variation = '<variants>';
                    try{
                        foreach($configurableChildren as $child_product){
                            if (!in_array($productModel->getStoreId(), $child_product->getStoreIds())){
                                continue;
                            }
                            
                            $is_variant_in_stock = ($child_product->getStockItem()->getIsInStock()) ? 1 : 0;
                            
                            if (method_exists($child_product, 'isSaleable')){
                                $is_variant_sellable = ($child_product->isSaleable()) ? 1 : 0;
                            } else {
                                $is_variant_sellable = '';
    //                             $is_variant_sellable = (Mage::getModel('catalog/product')
    //                                                     ->setStore($productModel->getStoreId())
    //                                                     ->setStoreId($productModel->getStoreId())
    //                                                     ->load($child_product->getId())
    //                                                     ->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                            }
                            
                            if (method_exists($child_product, 'getVisibility')){
                                $is_variant_visible = ($child_product->getVisibility()) ? 1 : 0;
                            } else {
                                $is_variant_visible = '';
    //                             $is_variant_visible = (Mage::getModel('catalog/product')
    //                                                    ->setStore($productModel->getStoreId())
    //                                                    ->setStoreId($productModel->getStoreId())
    //                                                    ->load($child_product->getId())
    //                                                    ->getVisibility());
                            }
                            
                            $variant_price = (array_key_exists($child_product->getId(), $simple_products_price)) ?
                                                $simple_products_price[$child_product->getId()] : '';
                            
                            $product_variation .= '<variant id="'.$child_product->getId().'" type="'.$child_product->getTypeID().
                                                    '" visibility="'.$is_variant_visible.'" is_in_stock="'.$is_variant_in_stock.'" is_seallable="'.$is_variant_sellable.'" price="'.$variant_price.'">';
                            $product_variation .= '<name><![CDATA['.$child_product->getName().']]></name>';
                            
                            $attributes = $child_product->getAttributes();
                            foreach ($attributes as $attribute) {
                                if (!$attribute['is_configurable'] || !in_array($attribute['store_label'], $configural_attributes)){ // || !$attribute->getIsVisibleOnFront()
                                    continue;
                                }
                                    
                                $product_variation .= '<variant_attribute is_configurable="1" is_filterable="'.$attribute->getis_filterable().
                                                        '" name="'.$attribute['store_label'].'" name_code="'.$attribute->getId().
                                                        '" value_code="'.$child_product->getData($attribute->getAttributeCode()).
                                                        '"><![CDATA['.$attribute->getFrontend()->getValue($child_product).
                                                        ']]></variant_attribute>';
                            }
                            $product_variation .= '</variant>';
                        }
                    } catch(Exception $e ){
                    } 
                    $product_variation .= '</variants>';
                    $row.=$product_variation;
                }
                
                $row.='<simpleproducts><![CDATA['.implode(',',$configurable_children_ids).']]></simpleproducts>';
            } else if ($productModel->getTypeID()=='grouped' && count($grouped_children_ids)>0){
                $row.='<simpleproducts><![CDATA['.implode(',',$grouped_children_ids).']]></simpleproducts>';
            }
            
            if($productModel->getTypeID() == 'simple'){
                $row.='<product_parents><![CDATA['.implode(',',$simple_product_parents).']]></product_parents>';
            }
        }

        $row.='<categories><![CDATA[' . implode(';', $categories) . ']]></categories>';
        $row.='</product>';
        return $helper->escapeXml($row);
    }

    private function _makeUpdateRow($batch,$attributes){

        $productId =         $batch['product_id'];

        $sku =               $batch['sku'];

        $storeId =           $batch['store_id'];

        $updatedate =        $batch['update_date'];

        $action =            $batch['action'];

        $currency=Mage::app()->getStore($storeId)->getCurrentCurrencyCode();

        if($productId!=null){

            $productModel=Mage::getModel('catalog/product')

                ->setStoreId($storeId)

                ->load($productId);

            if($productModel==null){

                return '';

            }

        }else{

            /*

             * FIX - Fatal error: Call to undefined method Mage_Catalog_Model_Resource_Product_Flat::loadAllAttributes()

             */

            $productModel=Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

            if($productModel==null){

                return '';

            }

            $productModel = $productModel->setStoreId($storeId);

            $productId=$productModel->getId();
        }

        if($productModel==null){

            return '';

        }

        $price       =$this->getPrice($productModel);

        $sku         =$productModel->getSku();

        $status      =$productModel->isInStock();

        $stockItem   = $productModel->getStockItem();

        $categoriesNames='';

        $categories = $productModel->getCategoryCollection()
            ->addAttributeToSelect('name');

        foreach($categories as $category) {
            $categoriesNames.=$category->getName().':'.$category->getId().';';
        }

        if($stockItem->getIsInStock()&&$status)
        {
            $sell=1;
        }else{
            $sell=0;
        }

        $productUrl       =Mage::helper('catalog/product')->getProductUrl($productId);

        $prodId           =$productModel->getId();

        $prodDesc         =$productModel->getDescription();

        $prodShortDesc    =$productModel->getShortDescription();

        $prodName         =$productModel->getName();

        $visibility       =$productModel->getVisibility();

        try{

            if(in_array($this->imageField,$this->standardImageFields)){

                $prodImage   =Mage::helper('catalog/image')->init($productModel, $this->imageField);

            }else{

                $function='get'.$this->imageField;

                $prodImage  =$productModel->$function();

            }

        }catch(Exception $e){

            $prodImage='';

        }

        if($productModel->getTypeID()=='configurable'){

            $configurableAttributes=$this->_getConfigurableAttributes($productModel);

            try{

                $priceRange=$this->_getPriceRange($productModel);

            }catch(Exception $e){

                $priceRange='price_min="" price_max=""';

            }

        }else{

            $priceRange='price_min="" price_max=""';

        }

        $row='<product '.$priceRange.' id="'.$prodId.'" type="'.$productModel->getTypeID().'" updatedate="'.$updatedate.'" currency="'.$currency.'" storeid="'.$storeId.'" visibility="'.$visibility.'" price="'.$price.'" url="'.$productUrl.'"  thumbs="'.$prodImage.'" selleable="'.$sell.'" action="'.$action.'" >';

        $row.='<description><![CDATA['.$prodDesc.']]></description>';

        $row.='<short><![CDATA['.$prodShortDesc.']]></short>';

        $row.='<name><![CDATA['.$prodName.']]></name>';

        $row.='<sku><![CDATA['.$sku.']]></sku>';

        if($attributes!=null){

            foreach($attributes as $attr){



                $action=$attr->getAttributeCode();



                $is_filterable=$attr->getis_filterable();



                if($attr->getfrontend_input()=='select'){



                    if($productModel->getData($action)){

                        $row.='<attribute is_filterable="'.$is_filterable.'" attribute_type="'.$attr->getfrontend_input().'"  name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getAttributeText($action).']]></attribute>';

                    }



                }elseif($attr->getfrontend_input()=='textarea'){



                    if($productModel->getData($action)){

                        $row.='<attribute is_filterable="'.$is_filterable.'" attribute_type="'.$attr->getfrontend_input().'"  name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';

                    }

                }elseif($attr->getfrontend_input()=='price'){



                    if($productModel->getData($action)){

                        $row.='<attribute is_filterable="'.$is_filterable.'" attribute_type="'.$attr->getfrontend_input().'"  name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';

                    }

                }elseif($attr->getfrontend_input()=='text'){



                    if($productModel->getData($action)){

                        $row.='<attribute is_filterable="'.$is_filterable.'" attribute_type="'.$attr->getfrontend_input().'"  name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';

                    }

                }elseif($attr->getfrontend_input()=='multiselect'){

                    if($productModel->getData($action)){

                        $values=$productModel->getResource()->getAttribute($action)->getFrontend()->getValue($productModel);



                        $row.='<attribute is_filterable="'.$is_filterable.'" name="'.$attr->getAttributeCode().'"><![CDATA['.$values.']]></attribute>';

                    }

                }

            }

            if($productModel->getTypeID()=='configurable' && count($configurableAttributes)>0){

                foreach($configurableAttributes as $attrName=>$confAttrN){

                    if(is_array($confAttrN) && array_key_exists('values',$confAttrN)){

                        $values=implode(' , ',$confAttrN['values']);

                        $row.='<attribute is_configurable="1" is_filterable="'.$confAttrN['is_filterable'].'" name="'.$attrName.'"><![CDATA['.$values.']]></attribute>';

                    }



                }

            }

        }

        $row.='<categories><![CDATA['.$categoriesNames.']]></categories>';

        $row.='</product>';



        return $row;

    }

    private function _makeRemoveRow($batch){
        $updatedate = $batch['update_date'];
        $action     = $batch['action'];
        $sku        = $batch['sku'];
        $productId  = $batch['product_id'];
        $storeId    = $batch['store_id'];

        $row='<product updatedate="'.$updatedate.'" action="'.$action.'" id="'.$productId.'" storeid="'.$storeId.'">';
        $row.='<sku><![CDATA['.$sku.']]></sku>';
        $row.='<id><![CDATA['.$productId.']]></id>';
        $row.='</product>';
        return $row;
    }

    private function _getConfigurableChildren($product){

        $childProducts = Mage::getModel('catalog/product_type_configurable')
            ->getUsedProducts(null,$product);
        return $childProducts;
    }
    
    private function _getGroupedChildren($product){
        return $product->getTypeInstance(true)->getAssociatedProducts($product);
    }
    
    private function _getSimpleProductParent($product){
        try{
            $parent_products_ids_list = Mage::getModel('catalog/product_type_configurable')
                    ->getParentIdsByChild($product->getId());
     
            return $parent_products_ids_list;
        } catch(Exception $e){
            return array();
        }
    }

    private function _getConfigurableAttributes($product){

        try{
            // Collect options applicable to the configurable product
            $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

            $attributeOptions = array();

            foreach ($productAttributeOptions as $productAttribute) {
                $attributeFull = Mage::getModel('eav/config')->getAttribute('catalog_product', $productAttribute['attribute_code']);

                foreach ($productAttribute['values'] as $attribute) {
                    $attributeOptions[$productAttribute['store_label']]['values'][] = $attribute['store_label'];

                }

                $attributeOptions[$productAttribute['store_label']]['is_filterable']=$attributeFull['is_filterable'];
                $attributeOptions[$productAttribute['store_label']]['frontend_input']=$attributeFull['frontend_input'];
            }
            return $attributeOptions;

        }catch(Exception $e){
            return array();
        }

    }

    private function _getPriceRange($product){
        $max = '';
        $min = '';
        
        $pricesByAttributeValues = array();
        $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
        $basePrice = $product->getFinalPrice();
        $items = $attributes->getItems();
        if (is_array($items)){
            foreach ($items as $attribute){
                $prices = $attribute->getPrices();
                if (is_array($prices)){
                    foreach ($prices as $price){
                        if ($price['is_percent']){ //if the price is specified in percents
                            $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
                        }
                        else { //if the price is absolute value
                            $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                        }
                    }
                }
            }
        }

        $simple = $product->getTypeInstance()->getUsedProducts();
        foreach ($simple as $sProduct){
            $totalPrice = $basePrice;
            foreach ($attributes as $attribute){
                $value = $sProduct->getData($attribute->getProductAttribute()->getAttributeCode());
                if (isset($pricesByAttributeValues[$value])){
                    $totalPrice += $pricesByAttributeValues[$value];
                }
            }
            if(!$max || $totalPrice > $max)
                $max = $totalPrice;
            if(!$min || $totalPrice < $min)
                $min = $totalPrice;
        }
        $priceRange='price_min="'.$min.'" price_max="'.$max.'"';
        return $priceRange;
    }
    
    private function _getSimpleProductsPriceOfConfigurable($product, $configurable_children){
        $simple_products_price = array();
        $pricesByAttributeValues = array();
        $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
        $basePrice = $product->getFinalPrice();
        $items = $attributes->getItems();
        if (is_array($items)){
            foreach ($items as $attribute){
                $prices = $attribute->getPrices();
                if (is_array($prices)){
                    foreach ($prices as $price){
                        if ($price['is_percent']){ //if the price is specified in percents
                            $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'] * $basePrice / 100;
                        }
                        else { //if the price is absolute value
                            $pricesByAttributeValues[$price['value_index']] = (float)$price['pricing_value'];
                        }
                    }
                }
            }
        }
        
        foreach ($configurable_children as $sProduct){
            $totalPrice = $basePrice;
            foreach ($attributes as $attribute){
                $value = $sProduct->getData($attribute->getProductAttribute()->getAttributeCode());
                if (isset($pricesByAttributeValues[$value])){
                    $totalPrice += $pricesByAttributeValues[$value];
                }
            }
            $simple_products_price[$sProduct->getId()] = $totalPrice;
        }
        return $simple_products_price;
    }

    private function _getOrdersPerProduct($store_id, $product_id_list, $month_interval){

        if (count($product_id_list) <= 0)

            return null;

        $id_str = implode(',', $product_id_list);

        $query = Mage::getResourceModel('sales/order_item_collection');

        $select = $query->getSelect()->reset(Zend_Db_Select::COLUMNS)

            ->columns(array('product_id','SUM(qty_ordered)'))

            ->where(new Zend_Db_Expr('store_id = ' . $store_id))

            ->where(new Zend_Db_Expr('product_id IN ('.$id_str.')'))

            ->where(new Zend_Db_Expr('created_at BETWEEN NOW() - INTERVAL '.$month_interval.' MONTH AND NOW()'))

            ->group(array('product_id'));



        $resource = Mage::getSingleton('core/resource');

        $readConnection = $resource->getConnection('core_read');

        $results = $readConnection->fetchAll($select);



        $orders_per_product = array();

        foreach ($results as $res){

            $orders_per_product[$res['product_id']] = (int)$res['SUM(qty_ordered)'];

        }

        return $orders_per_product;

    }

    private function _getPrice($product){
        $price = 0;
        $helper=Mage::helper('autocompleteplus_autosuggest');
        if ($product->getTypeId()=='grouped'){
            $helper->prepareGroupedProductPrice($product);
            $_minimalPriceValue = $product->getPrice();
            if($_minimalPriceValue){
                $price=$_minimalPriceValue;
            }
        }elseif($product->getTypeId()=='bundle'){
            if(!$product->getFinalPrice()){
                $price=$helper->getBundlePrice($product);
            }else{
                $price=$product->getFinalPrice();
            }
        }else{
            $price       =$product->getFinalPrice();
        }
        if(!$price){
            $price=0;
        }
        return $price;
    }

    /**

     * @param $storeId

     */

    private function _initCatalogCommonFields($storeId)
    {
        $this->imageField=Mage::getStoreConfig('autocompleteplus/config/imagefield');

        if (!$this->imageField) {
            $this->imageField = 'thumbnail';
        }

        $this->useAttributes = Mage::getStoreConfig('autocompleteplus/config/attributes');

        $this->currency = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();

        $this->standardImageFields = array('image', 'small_image', 'thumbnail');

        $productScheme = Mage::getModel('catalog/product');

        if($this->useAttributes!='0'){
            $this->attributes = Mage::getResourceModel('eav/entity_attribute_collection')

                ->setEntityTypeFilter($productScheme->getResource()->getTypeId())

                ->addFieldToFilter('is_user_defined', '1') // This can be changed to any attribute code

                ->load(false);
        }

    }

}