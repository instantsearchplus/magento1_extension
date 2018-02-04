<?php
/**
 * InstantSearchPlus (Autosuggest)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 * @package    InstantSearchPlus
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Autocompleteplus_Autosuggest_Helper_Data extends Mage_Core_Helper_Abstract
{
//     private $server_url = 'http://0-2vk.acp-magento.appspot.com';
    private $server_url = 'http://magento.instantsearchplus.com';

    protected $_authKey;

    public function getServerUrl(){
        return $this->server_url;
    }

    public function getVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version;
    }

    public function getConfigDataByFullPath($path){

        if (!$row = Mage::getSingleton('core/config_data')->getCollection()->getItemByColumnValue('path', $path)) {
            $conf = Mage::getSingleton('core/config')->init()->getXpath('/config/default/'.$path);
            if(is_array($conf)){
                $value = array_shift($conf);
            }else{
                return '';
            }

        } else {
            $value = $row->getValue();
        }

        return $value;

    }

    public function getConfigMultiDataByFullPath($path){

        if (!$rows = Mage::getSingleton('core/config_data')->getCollection()->getItemsByColumnValue('path', $path)) {
            $conf = Mage::getSingleton('core/config')->init()->getXpath('/config/default/'.$path);
            $value = array_shift($conf);
        } else {
            $values=array();
            foreach($rows as $row){
                $values[$row->getScopeId()]=$row->getValue();
            }
        }

        return $values;

    }

    public function sendCurl($command){

        if(isset($ch)) unset($ch);

        if(function_exists('curl_setopt')){
            $ch              = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            $str=curl_exec($ch);
        }else{
            $str='failed';
        }

        return $str;


    }

    public function getKey(){

        if(!$this->_authKey){
            $_tableprefix   = (string)Mage::getConfig()->getTablePrefix();
            $sqlFetch       = 'SELECT authkey FROM '. $_tableprefix.'autocompleteplus_config WHERE id = 1 LIMIT 1';
            $read           = Mage::getSingleton('core/resource')->getConnection('core_read');
            $this->_authKey = $read->fetchOne($sqlFetch);
        }

        return $this->_authKey;
    }

    public function getBothKeys(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if(!$tblExist){return;}

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $key=$licenseData[0]['licensekey'];

        $authKey= $licenseData[0]['authkey'];

        $res=array('uuid'=>$key,'authkey'=>$authKey);

        return $res;
    }

    public static function sendPostCurl($command, $data=array(),$cookie_file='genCookie.txt') {

        if(isset($ch)) unset($ch);

        if(function_exists('curl_setopt')){

            $ch              = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20100101 Firefox/21.0');
            //curl_setopt($ch,CURLOPT_POST,0);
            if(!empty($data)){
                curl_setopt_array($ch, array(
                    CURLOPT_POSTFIELDS => $data,
                ));
            }


            //  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //      'Connection: Keep-Alive',
            //      'Keep-Alive: 800'
            //  ));

            $str=curl_exec($ch);

        }else{
            $str='failed';
        }

        return $str;
    }

    public function prepareGroupedProductPrice($groupedProduct)
    {
        $aProductIds = $groupedProduct->getTypeInstance()->getChildrenIds($groupedProduct->getId());

        $prices = array();
        foreach ($aProductIds as $ids) {
            foreach ($ids as $id) {
                try{
                    $aProduct = Mage::getModel('catalog/product')->load($id);
                    $prices[] = $aProduct->getPriceModel()->getPrice($aProduct);
                } catch (Exception $e){
                    continue;
                }
            }
        }

        krsort($prices);
        try{
            if (count($prices) > 0){
                $groupedProduct->setPrice($prices[0]);
            } else {
                $groupedProduct->setPrice(0);
            }
        } catch (Exception $e){
            $groupedProduct->setPrice(0);
        }

        // or you can return price
    }

    public function getBundlePrice($product) {

        $optionCol= $product->getTypeInstance(true)
            ->getOptionsCollection($product);
        $selectionCol= $product->getTypeInstance(true)
            ->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );
        $optionCol->appendSelections($selectionCol);
        $price = $product->getPrice();

        foreach ($optionCol as $option) {
            if($option->required) {
                $selections = $option->getSelections();
                $selPricesArr=array();

                if(is_array($selections)){

                    foreach($selections as $s){
                        $selPricesArr[]=$s->price;
                    }



                    $minPrice = min($selPricesArr);

                    if($product->getSpecialPrice() > 0) {
                        $minPrice *= $product->getSpecialPrice()/100;
                    }

                    $price += round($minPrice,2);

                }
            }
        }
        return $price;

    }

    public function getMultiStoreDataJson(){

        $websites=Mage::getModel('core/website')->getCollection();

        $multistoreData=array();
        $multistoreJson='';
        $useStoreCode=$this->getConfigDataByFullPath('web/url/use_store');
        $mage=Mage::getVersion();
        $ext=Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $version=array('mage'=>$mage,'ext'=>$ext);

        //getting site url
        $url=$this->getConfigDataByFullPath('web/unsecure/base_url');

        //getting site owner email
        $storeMail=$this->getConfigDataByFullPath('autocompleteplus/config/store_email');

        if(!$storeMail){

            $storeMail=$this->getConfigDataByFullPath('trans_email/ident_general/email');
        }

        $collection=Mage::getModel('catalog/product')->getCollection();
        //$productCount=$collection->count();


        $storesArr=array();
        foreach($websites as $website){
            $code=$website->getCode();
            $stores=$website->getStores();
            foreach($stores as $store){
                $storesArr[$store->getStoreId()]=$store->getData();
            }
        }

        if(count($storesArr)==1){
            try{
                $dataArr = array(
//                         'stores'  => array(array_pop($storesArr)),
                        'stores'  => array_pop($storesArr),
                        'version' => $version
                );
    	    } catch (Exception $e){
                $dataArr = array(
                    'stores'  => $multistoreData,
                    'version' => $version
                );
    	    }

            $dataArr['site']  = $url;
            $dataArr['email'] = $storeMail;

            $multistoreJson = json_encode($dataArr);

        }else{

            $storeUrls=$this->getConfigMultiDataByFullPath('web/unsecure/base_url');
            $locales=$this->getConfigMultiDataByFullPath('general/locale/code');
            $storeComplete=array();

            foreach($storesArr as $key=>$value){

                if(!$value['is_active']){
                    continue;
                }

                $storeComplete=$value;
                if(array_key_exists($key,$locales)){
                    $storeComplete['lang']=$locales[$key];
                }else{
                    $storeComplete['lang']=$locales[0];
                }

                if(array_key_exists($key,$storeUrls)){
                    $storeComplete['url']=$storeUrls[$key];
                }else{
                    $storeComplete['url']=$storeUrls[0];
                }

                if($useStoreCode){
                    $storeComplete['url']=$storeUrls[0].$value['code'];
                }

                $multistoreData[]=$storeComplete;
            }

            $dataArr=array(
                'stores'=>$multistoreData,
                'version'=>$version
            );

            $dataArr['site']=$url;
            $dataArr['email']=$storeMail;
            //$dataArr['product_count']=$productCount;

            $multistoreJson=json_encode($dataArr);

        }
        Mage::log($multistoreJson,null,'autocomplete.log');

        return $multistoreJson;
    }

    public function getExtensionConflict($all_conflicts = false){
        $all_rewrite_classes = array();
        $node_type_list = array('model', 'helper', 'block');

        foreach ($node_type_list as $node_type){
            foreach (Mage::getConfig()->getNode('modules')->children() as $name => $module) {
                if ($module->codePool == 'core' || $module->active != 'true'){
                    continue;
                }
                $config_file_path = Mage::getConfig()->getModuleDir('etc', $name) . DS . 'config.xml';
                $config = new Varien_Simplexml_Config();
                $config->loadString('<config/>');
                $config->loadFile($config_file_path);
                $config->extend($config, true);

                $nodes = $config->getNode()->global->{$node_type . 's'};
                if (!$nodes)
                    continue;
                foreach($nodes->children() as $node_name => $config) {
                    if ($config->rewrite){  // there is rewrite for current config
                        foreach($config->rewrite->children() as $class_tag => $derived_class){
                            $base_class_name = $this->_getMageBaseClass($node_type, $node_name, $class_tag);

                            $lead_derived_class = '';
                            $conf = Mage::getConfig()->getNode()->global->{$node_type . 's'}->{$node_name};
                            if (isset($conf->rewrite->$class_tag)){
                                $lead_derived_class = (string)$conf->rewrite->$class_tag;
                            }
                            if ($derived_class == ''){
                                $derived_class = $lead_derived_class;
                            }

                            if (empty($all_rewrite_classes[$base_class_name])){
                                $all_rewrite_classes[$base_class_name] = array(
                                    'derived' => array((string)$derived_class),
                                    'lead'    => (string)$lead_derived_class,
                                    'tag'     => $class_tag,
                                    'name'    => array((string)$name)
                                );
                            }else{
                                array_push($all_rewrite_classes[$base_class_name]['derived'], (string)$derived_class);
                                array_push($all_rewrite_classes[$base_class_name]['name'], (string)$name);
                            }
                        }
                    }
                }
            }
        }
        if ($all_conflicts){
            return $all_rewrite_classes;
        }

        $isp_rewrite_classes = array();
        $isp_module_name = 'Autocompleteplus_Autosuggest';
        foreach ($all_rewrite_classes as $base => $conflict_info){
            if (in_array($isp_module_name, $conflict_info['name'])){        // if isp extension rewrite this base class
                if (count($conflict_info['derived']) > 1){                  // more then 1 class rewrite this base class => there is a conflict
                    $isp_rewrite_classes[$base] = $conflict_info;
                }
            }
        }
        return $isp_rewrite_classes;
    }

    protected function _getMageBaseClass($node_type, $node_name, $class_tag){
        $config = Mage::getConfig()->getNode()->global->{$node_type . 's'}->$node_name;

        if (!empty($config)) {
            $className = $config->getClassName();
        }
        if (empty($className)) {
            $className = 'mage_'.$node_name.'_'.$node_type;
        }
        if (!empty($class_tag)) {
            $className .= '_'.$class_tag;
        }
        return uc_words($className);
    }

    /**
     *  Checksum functionality
     */
    public function isChecksumTableExists(){
        $table_prefix = (string)Mage::getConfig()->getTablePrefix();
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        if ($read->showTableStatus($table_prefix . 'autocompleteplus_checksum')){
            return true;
        } else {
            return false;
        }
    }

    public function calculateChecksum($product){
        $product_id             = $product->getId();
        $product_title          = $product->getName();
        $product_description    = $product->getDescription();
        $product_short_desc     = $product->getShortDescription();
        $product_url            = $product->getUrlPath();   //Mage::helper('catalog/product')->getProductUrl($product_id); | $product->getProductUrl()
        $product_visibility     = $product->getVisibility();
        $product_in_stock       = $product->isInStock();
        $product_price          = (float)$product->getPrice();
        try{
            $product_thumb_url = '/' . $product->getImage();

//             $product_thumb_url = $product->getThumbnailUrl();  //Mage::helper('catalog/image')->init($product, 'thumbnail');
//             $thumb_pattern = '/\/[^\/]+(?![^\/]*\/)/';
//             if (preg_match($thumb_pattern, $product_thumb_url, $matches) && count($matches) > 0){
//                 $product_thumb_url = $matches[0];
//             } else {
//                 $product_thumb_url = '';
//             }
        } catch (Exception $e){
            $product_thumb_url = '';
        }
        $product_type = $product->getTypeID();

        $checksum_string = $product_id .  $product_title . $product_description . $product_short_desc . $product_url .
                        $product_visibility . $product_in_stock . $product_price . $product_thumb_url . $product_type;

        $checksum_md5 = md5($checksum_string);
        return $checksum_md5;
    }

    public function getSavedChecksum($table_prefix, $read, $product_id, $store_id){
        $sql_fetch = 'SELECT checksum FROM ' . $table_prefix . 'autocompleteplus_checksum WHERE identifier=? AND store_id=?';
        $updates = $read->fetchAll($sql_fetch, array($product_id, $store_id));
        if($updates && (count($updates) != 0)){
            return $updates[0]['checksum'];
        } else {
            return '';
        }
    }

    public function updateSavedProductChecksum($table_prefix, $read, $write, $product_id, $sku, $store_id, $checksum){
        if ($product_id == null || $sku == null){
            return;
        }
        $sql_fetch = 'SELECT checksum FROM ' . $table_prefix . 'autocompleteplus_checksum WHERE identifier=? AND store_id=?';
        $updates = $read->fetchAll($sql_fetch, array($product_id, $store_id));

        if($updates && (count($updates) != 0)){
            if ($updates[0]['checksum'] != $checksum){
                $sql = 'UPDATE '. $table_prefix.'autocompleteplus_checksum SET checksum=? WHERE identifier=? AND store_id=?';
                $write->query($sql, array($checksum, $product_id, $store_id));
            }
        }else{
            $sql = 'INSERT INTO '. $table_prefix.'autocompleteplus_checksum  (identifier, sku, store_id, checksum) VALUES (?,?,?,?)';
            $write->query($sql, array($product_id, $sku, $store_id, $checksum));
        }
    }

    public function updateDeletedProductChecksum($table_prefix, $read, $write, $product_id, $sku, $store_id){
        if ($product_id == null){
            return;
        }
        $sql_fetch = 'SELECT * FROM ' . $table_prefix . 'autocompleteplus_checksum WHERE identifier=? AND store_id=?';
        $updates = $read->fetchAll($sql_fetch, array($product_id, $store_id));

        if($updates && (count($updates) != 0)){
            $sql = 'DELETE FROM '. $table_prefix.'autocompleteplus_checksum WHERE identifier=? AND store_id=?';
            $write->query($sql, array($product_id, $store_id));
        }
    }

    private function setUpdateNeededForProduct($read, $write, $product_id, $product_sku, $store_id){
        if ($product_id == null){
            return;
        }
        if ($product_sku == null){
            $product_sku = 'dummy_sku';
        }
        try{
            $table_prefix = (string)Mage::getConfig()->getTablePrefix();
            $is_table_exist = $write->showTableStatus($table_prefix.'autocompleteplus_batches');
            if (!$is_table_exist)   // table not exists
                return;
            
            $sql_fetch = 'SELECT * FROM '. $table_prefix.'autocompleteplus_batches WHERE product_id=? AND store_id=?';
            $updates = $read->fetchAll($sql_fetch, array($product_id, $store_id));

            if ($updates && (count($updates) != 0)){
                $sql = 'UPDATE '. $table_prefix.'autocompleteplus_batches  SET update_date=?,action=? WHERE product_id=? AND store_id=?';
                $write->query($sql, array(strtotime('now'), "update", $product_id, $store_id));
            }else{
                $sql='INSERT INTO '. $table_prefix.'autocompleteplus_batches (product_id,store_id,update_date,action,sku) VALUES (?,?,?,?,?)';
                $write->query($sql, array($product_id, $store_id, strtotime('now'), "update", $product_sku));
            }

        }catch(Exception $e){
            Mage::log('Exception raised in setUpdateNeededForProduct() - ' . $e->getMessage(), null, 'autocompleteplus.log');
            $this->ispErrorLog('Exception raised in setUpdateNeededForProduct() - ' . $e->getMessage());
        }
    }

    public function compareProductsChecksum($from, $count, $store_id = null){
        $num_of_updates = 0;
        if (!$this->isChecksumTableExists())
            return;

        $products = Mage::getModel('catalog/product')->getCollection();
        if ($store_id){
            $products->addStoreFilter($store_id);
        }
        $products->getSelect()->limit($count, $from);
        $products->load();

        $table_prefix = (string)Mage::getConfig()->getTablePrefix();
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        foreach ($products as $product) {
            try{
                $product_collection_data = $product->getData();
                $product_model = Mage::getModel('catalog/product')
                    ->setStore($store_id)->setStoreId($store_id)
                    ->load($product_collection_data['entity_id']);
                
                $current_checksum = $this->getSavedChecksum($table_prefix, $read, $product_model->getId(), $store_id );
                $new_checksum = $this->calculateChecksum($product_model);
            }catch(Exception $e){
                Mage::log('Exception raised in compareProductsChecksum() on id: ' . $product->getId() . ' -> ' . $e->getMessage(), null, 'autocompleteplus.log');
                $this->ispErrorLog('Exception raised in compareProductsChecksum() on id: ' . $product->getId() . ' -> ' . $e->getMessage());
                return 0;
            }
            if ($current_checksum == '' || $current_checksum != $new_checksum){
                $num_of_updates++;
                $this->updateSavedProductChecksum($table_prefix, $read, $write, $product_model->getId(), $product_model->getSku(),
                    $store_id, $new_checksum);
                $this->setUpdateNeededForProduct($read, $write, $product_model->getId(), $product_model->getSku(), $store_id);
            }
        }
        return $num_of_updates;
    }

    public function deleteProductFromTables($read, $write, $table_prefix, $product_id, $store_id){
        $dt = strtotime('now');
        $sku = 'dummy_sku';
        $sqlFetch = 'SELECT * FROM '. $table_prefix.'autocompleteplus_batches WHERE product_id = ? AND store_id=?';
        $updates = $read->fetchAll($sqlFetch, array($product_id, $store_id));

        if($updates && count($updates) != 0){
            $sql = 'UPDATE '. $table_prefix.'autocompleteplus_batches SET update_date=?,action=? WHERE product_id = ? AND store_id = ?';
            $write->query($sql, array($dt, "remove", $product_id, $store_id));
        } else {
            $sql='INSERT INTO '. $table_prefix.'autocompleteplus_batches  (product_id,store_id,update_date,action,sku) VALUES (?,?,?,?,?)';
            $write->query($sql, array($product_id, $store_id, $dt, "remove", $sku));
        }

        $this->updateDeletedProductChecksum($table_prefix, $read, $write, $product_id, $sku, $store_id);
    }

    public function ispLog($log){
        Mage::log($log, null, 'autocompleteplus.log');
    }

    public function ispErrorLog($log){
        $uuid       = $this->getUUID();
        $site_url   = $this->getConfigDataByFullPath('web/unsecure/base_url');
        $store_id   = Mage::app()->getStore()->getStoreId();

        $server_url = $this->server_url . '/magento_logging_error';
        $request = $server_url . '?uuid=' . $uuid . '&site_url=' . $site_url . '&store_id=' . $store_id . '&msg=' . urlencode($log);

        $resp = $this->sendCurl($request);
    }

    public function getUUID(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if(!$tblExist){return '';}

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $key=$licenseData[0]['licensekey'];

        return $key;

    }

    public function getIsReachable(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if(!$tblExist){return '';}

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $is_reachable=$licenseData[0]['is_reachable'];

        return $is_reachable;

    }
    
    public function getServerEndPoint(){
        try{
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $_tableprefix = (string)Mage::getConfig()->getTablePrefix();
            $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');
        
            if(!$tblExist){
                return '';
            }
        
            $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';
            $licenseData=$read->fetchAll($sql);
            if (array_key_exists('server_type', $licenseData[0])){
                $key = $licenseData[0]['server_type'];
            } else {
                $key = '';
            }
        } catch(Exception $e){
            $key = '';
        }
        return $key;
    }
    
    public function setServerEndPoint($end_point){
        try{
            $_tableprefix = (string)Mage::getConfig()->getTablePrefix();
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');
    
            if(!$tblExist){
                return;
            }
    
            $sqlFetch = 'SELECT * FROM '. $_tableprefix.'autocompleteplus_config WHERE id = 1';
            $updates = $write->fetchAll($sqlFetch);
    
            if($updates&&count($updates)!=0){
                $sql='UPDATE '. $_tableprefix.'autocompleteplus_config SET server_type=? WHERE id = 1';
                $write->query($sql, array($end_point));
            }else{
                Mage::log('cant update server_type',null,'autocompleteplus.log');
            }
        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'autocompleteplus.log');
        }
    }

    public function getErrormessage(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if(!$tblExist){return '';}

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $errormessage=$licenseData[0]['errormessage'];

        return $errormessage;

    }

    public function getIfSyncWasInitiated(){
        $collection = Mage::getModel('autocompleteplus_autosuggest/pusher')->getCollection();

        $count=$collection->count();

        if($count==0){
            return false;
        }else{
            return true;
        }
    }

    public function getPushId(){

        $collection = Mage::getModel('autocompleteplus_autosuggest/pusher')->getCollection()
            ->addFilter('sent',0);

        $collection->getSelect()->limit(1);

        $collection->load();

        $id='';

        foreach ($collection as $p) {
            $id=$p->getId();
        }

        return $id;
    }

    public function getPushUrl($id=null){

        if($id==null){
            $id=$this->getPushId();
        }

        $url=Mage::getUrl();//'',array('pushid'=>$id));

        if (strpos($url, 'index.php') !== FALSE){
            $url=$url.'/autocompleteplus/products/pushbulk/pushid/'.$id;
        }else{
            $url=$url.'index.php/autocompleteplus/products/pushbulk/pushid/'.$id;
        }
        return $url;

    }

    public function escapeXml($xml){
//        $pairs = array(
//            "\x03" => "&#x03;",
//            "\x05" => "&#x05;",
//            "\x0E" => "&#x0E;",
//            "\x16" => "&#x16;",
//        );
//        $xml = strtr($xml, $pairs);

        $xml=preg_replace('/[\x00-\x1f]/', '', $xml);
        return $xml;
    }

    
    /**
     * Get the session cookie value
     * protected with a salt (the store encryption key)
     * @return string
     */
    public function getSessionId()
    {
        return md5(Mage::app()->getCookie()->get('frontend') . $this->_getEncryptionKey());
    }

    /**
     * Return encryption key in Magento to use as salt
     * Requires getting from configNode so that it is backward
     * compatible with later versions
     * @return string
     */
    protected function _getEncryptionKey()
    {
        return (string) Mage::getConfig()->getNode('global/crypt/key');
    }

}