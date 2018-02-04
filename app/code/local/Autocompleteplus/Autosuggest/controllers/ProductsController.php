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
class Autocompleteplus_Autosuggest_ProductsController extends Mage_Core_Controller_Front_Action
{
    protected $_storeId;
    const MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION = 250;

    public function sendAction(){
        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

        $startInd     = $post['offset'];

        $count        = $post['count'];

        $storeId=isset($post['store']) ? $post['store'] : '';
        if ($storeId == ''){
            $storeId=isset($post['store_id']) ? $post['store_id'] : '';
        }

        $orders=isset($post['orders']) ? $post['orders'] : '';

        $month_interval=isset($post['month_interval']) ? $post['month_interval'] : '';

        $checksum=isset($post['checksum']) ? $post['checksum'] : '';

        $catalogModel=Mage::getModel('autocompleteplus_autosuggest/catalog');

        $xml=$catalogModel->renderCatalogXml($startInd,$count,$storeId,$orders,$month_interval,$checksum);

        header('Content-type: text/xml');
        echo $xml;
        die;
    }

    public function sendupdatedAction(){

        date_default_timezone_set('Asia/Jerusalem');

        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

        $count        = $post['count'];

        if(!isset($post['from'])){
            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'767',
                'error_details'=>'The "from" parameter is mandatory'
            );
            echo json_encode($returnArr);
            die;
        }
        $from = $post['from'];

        if(isset($post['to'])){
            $to   = $post['to'];
        }else{
            $to   = strtotime('now');
        }

        $storeId='';

        if(isset($post['store_id'])){
            $storeId   = $post['store_id'];
        }

        $catalogModel=Mage::getModel('autocompleteplus_autosuggest/catalog');

        $xml=$catalogModel->renderUpdatesCatalogXml($count,$from,$to,$storeId);

        header('Content-type: text/xml');
        echo $xml;
        die;

    }

    private function __checkAccess(){

        $post = $this->getRequest()->getParams();

        $key=Mage::getModel('autocompleteplus_autosuggest/observer')->getUUID();

        if(isset($post['key'])&&$post['key']==$key){
            return true;
        }else{
            return false;
        }

    }

    public function checkinstallAction(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $key=$licenseData[0]['licensekey'];

        if(strlen($key)>0&&$key!='failed'){
            echo 'the key exists';
        }else{
            echo 'no key inside';
        }

    }

    public function versAction(){
        $mage = Mage::getVersion();
        $ext =  Mage::helper('autocompleteplus_autosuggest')->getVersion();
        try{
            $num_of_products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getStoreId())
                ->getSize();
        } catch (Exception $e){
            $num_of_products = -1;
        }

        if (method_exists('Mage' , 'getEdition')){
            $edition = Mage::getEdition();
        } else {
            $edition = 'Community';
        }

        $helper     = Mage::helper('autocompleteplus_autosuggest');
        $uuid       = $helper->getUUID();
        $site_url   = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $store_id   = Mage::app()->getStore()->getStoreId();

        $result = array('mage' => $mage,
            'ext' => $ext,
            'num_of_products' => $num_of_products,
            'edition' => $edition,
            'uuid' => $uuid,
            'site_url' => $site_url,
            'store_id' => $store_id
        );

        $post = $this->getRequest()->getParams();

        if (array_key_exists('modules', $post))
            $get_modules = $post['modules'];
        else
            $get_modules = false;
        if ($get_modules){
            try{
                $modules_array = array();
                foreach (Mage::getConfig()->getNode('modules')->children() as $name => $module) {
                    if ($module->codePool != 'core' && $module->active == 'true'){
                        $modules_array[$name] = $module;
                    }
                }
            } catch (Exception $e){
                $modules_array = array();
            }
            $result['modules'] = $modules_array;
        }
        echo json_encode($result);die;
    }

    public function getNumOfProductsAction()
    {
        $catalogReport = Mage::getModel('autocompleteplus_autosuggest/catalogreport');
        $helper        = Mage::helper('autocompleteplus_autosuggest');

        $result = array('num_of_products'               => $catalogReport->getEnabledProductsCount(),
                        'num_of_disabled_products'      => $catalogReport->getDisabledProductsCount(),
                        'num_of_searchable_products'    => $catalogReport->getSearchableProductsCount(),
                        'num_of_searchable_products2'   => $catalogReport->getSearchableProducts2Count(),
                        'uuid'                          => $helper->getUUID(),
                        'site_url'                      => $helper->getConfigDataByFullPath('web/unsecure/base_url'),
                        'store_id'                      => $catalogReport->getCurrentStoreId()
        );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

    public function getConflictAction(){
        $post = $this->getRequest()->getParams();
        if (array_key_exists('all', $post))
            $get_all_conflicts = $post['all'];
        else
            $get_all_conflicts = false;

        $helper = Mage::helper('autocompleteplus_autosuggest');
        if ($get_all_conflicts){
            $result = $helper->getExtensionConflict(true);
        }else{
            $result = $helper->getExtensionConflict();
        }
        echo json_encode($result);die;
    }

    public function getstoresAction(){

        $helper=Mage::helper('autocompleteplus_autosuggest');

        echo $helper->getMultiStoreDataJson();
        die;
    }

    public function updateemailAction(){

        $helper=Mage::helper('autocompleteplus_autosuggest');

        $data = $this->getRequest()->getPost();

        $email=$data['email'];
        $uuid=$helper->getUUID();

        Mage::getModel('core/config')->saveConfig('autocompleteplus/config/store_email',$email);

        $params=array(
            'uuid'=>$uuid,
            'email'=>$email
        );

        $command="http://magento.autocompleteplus.com/ext_update_email";

        $res=$helper->sendPostCurl($command,$params);

        $result=json_decode($res);

        if($result->status=='OK'){
            echo 'Your email address was updated!';
        }
    }

    public function updatesitemapAction(){

        $helper=Mage::helper('autocompleteplus_autosuggest');

        $key=$helper->getUUID();

        $url=$helper->getConfigDataByFullPath('web/unsecure/base_url');

        if($key!='InstallFailedUUID' && $key!='failed'){

            $stemapUrl='Sitemap:http://magento.instantsearchplus.com/ext_sitemap?u='.$key.PHP_EOL;

            $robotsPath=Mage::getBaseDir().DS.'robots.txt';

            $write=false;

            if(file_exists($robotsPath)){
                if( strpos(file_get_contents($robotsPath),$stemapUrl) == false) {
                    $write=true;
                }
            }else{

                if(is_writable(Mage::getBaseDir())){

                    //create robots sitemap
                    file_put_contents($robotsPath,$stemapUrl);
                }else{

                    //write message that directory is not writteble
                    $command="http://magento.autocompleteplus.com/install_error";

                    $data=array();
                    $data['site']=$url;
                    $data['msg']='Directory '.Mage::getBaseDir().' is not writable.';
                    $res=$helper->sendPostCurl($command,$data);
                }
            }

            if($write){
                if(is_writable($robotsPath)){

                    //append sitemap
                    file_put_contents($robotsPath, $stemapUrl, FILE_APPEND | LOCK_EX);
                }else{
                    //write message that file is not writteble
                    $command="http://magento.autocompleteplus.com/install_error";

                    $data=array();
                    $data['site']=$url;
                    $data['msg']='File '.$robotsPath.' is not writable.';
                    $res=$helper->sendPostCurl($command,$data);
                }
            }

        }
    }

    protected function _setUUID($key){

        try{

            $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

            $read = Mage::getSingleton('core/resource')->getConnection('core_read');

            $write = Mage::getSingleton('core/resource')->getConnection('core_write');

            $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

            if(!$tblExist){return;}

            $sqlFetch    ='SELECT * FROM '. $_tableprefix.'autocompleteplus_config WHERE id = 1';

            $updates=$write->fetchAll($sqlFetch);

            if($updates&&count($updates)!=0){

                $sql='UPDATE '. $_tableprefix.'autocompleteplus_config  SET licensekey=? WHERE id = 1';

                $write->query($sql, array($key));

            }else{

                $sql='INSERT INTO '. $_tableprefix.'autocompleteplus_config  (licensekey) VALUES (?)';

                $write->query($sql, array($key));

            }


        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'autocompleteplus.log');
        }

    }

    public function getIspUuidAction(){

        $helper = Mage::helper('autocompleteplus_autosuggest');

        echo $helper->getUUID();
    }

    public function geterrormessageAction(){

        $helper = Mage::helper('autocompleteplus_autosuggest');

        echo $helper->getErrormessage();
    }

    public function setIspUuidAction(){
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $url_domain = 'http://magento.instantsearchplus.com/update_uuid';
        $storeId = Mage::app()->getStore()->getStoreId();
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $url = $url_domain . '?store_id=' . $storeId . '&site_url=' . $site_url;

        $helper = Mage::helper('autocompleteplus_autosuggest');
        $resp = $helper->sendCurl($url);
        $response_json = json_decode($resp);

        if (array_key_exists('uuid', $response_json)){
            if (strlen($response_json->uuid) == 36 && substr_count($response_json->uuid, '-') == 4){
                $this->_setUUID($response_json->uuid);
            }
        }
    }

    public function checkDeletedAction(){
        $helper = Mage::helper('autocompleteplus_autosuggest');
        if (!$helper->isChecksumTableExists()){
            return;
        }
        $time_stamp = time();

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table_prefix = (string)Mage::getConfig()->getTablePrefix();

        $post = $this->getRequest()->getParams();
        if (array_key_exists('store_id', $post)){
            $store_id = $post['store_id'];
        }else{
            $store_id = Mage::app()->getStore()->getStoreId();          // default
        }

        $sql_fetch = 'SELECT identifier FROM ' . $table_prefix . 'autocompleteplus_checksum WHERE store_id=?';
        $updates = $read->fetchPairs($sql_fetch, array($store_id));     // empty array if fails
        if (empty($updates)){
            return;
        }

        $checksum_ids = array_keys($updates);   // array of all checksum table identifiers        
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addFieldToFilter('entity_id',array('in'=>$checksum_ids));
        $found_ids = $collection->getAllIds();

        $removed_products_list = array_diff($checksum_ids, $found_ids);     // list of identifiers that are not present in the store (removed at some point)
        $removed_ids = array();

        // removing non-existing identifiers from checksum table
        if (!empty($removed_products_list)){
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql_delete = 'DELETE FROM ' . $table_prefix . 'autocompleteplus_checksum WHERE identifier IN (' . implode(',', $removed_products_list) . ')';
            $write->query($sql_delete);

            foreach ($removed_products_list as $product_id){
                $helper->deleteProductFromTables($read, $write, $table_prefix, $product_id, $store_id);
                $removed_ids[] = $product_id;
            }
        }

        $args = array('removed_ids' 		=> $removed_ids,
            'uuid' 	            => $helper->getUUID(),
            'store_id'            => $store_id,
            'latency'             => time() - $time_stamp,         // seconds
        );
        echo json_encode($args);    // returning the summary
    }

    public function checksumAction(){
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $checksum_server = $helper->getServerUrl();
        if (!$helper->isChecksumTableExists()){
            $helper->ispErrorLog('checksum table not exist');
            exit(json_encode(array('status' => 'checksum table not exist')));
        }
        $max_exe_time = -1;

        $post = $this->getRequest()->getParams();
        if (array_key_exists('store_id', $post)){
            $store_id = $post['store_id'];
        }else{
            $store_id = Mage::app()->getStore()->getStoreId();      // default
        }
        if (array_key_exists('count', $post)){
            $count = $post['count'];
        }else{
            $count = self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION;  // default
        }
        if (array_key_exists('offset', $post))
            $start_index = $post['offset'];
        else
            $start_index = 0;               // default
        if (array_key_exists('timeout', $post))
            $php_timeout = $post['timeout'];
        else
            $php_timeout = -1;              // default
        if (array_key_exists('is_single', $post))
            $is_single = $post['is_single'];
        else
            $is_single = 0;                 // default

        if ($count > self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION && $php_timeout != -1){
            $max_exe_time = ini_get('max_execution_time');
            ini_set('max_execution_time', $php_timeout);                   // 1 hour ~ 60*60
        }

        $uuid = $helper->getUUID();
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

        $collection = Mage::getModel('catalog/product')->getCollection();
        if ($store_id){
            $collection->addStoreFilter($store_id);
        }
        $num_of_products = $collection->getSize();

        if ($count + $start_index > $num_of_products){
            $count = $num_of_products - $start_index;
        }

        // sending log to the server        
        $log_msg = 'Update checksum is starting...';
        $log_msg .= (' number of products in this store: ' . $num_of_products . ' | from: ' . $start_index . ', to: ' . ($start_index + $count));
        $server_url = $checksum_server . '/magento_logging_record';
        $request = $server_url . '?uuid=' . $uuid . '&site_url=' . $site_url . '&msg=' . urlencode($log_msg);
        if ($store_id)
            $request .= '&store_id=' . $store_id;
        $resp = $helper->sendCurl($request);

        $start_time = time();
        $num_of_updated_checksum = 0;
        if($count > self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION){
            $iter = $start_index;
            while ($iter < $count){
                // start updating the checksum table if needed
                $num_of_updated_checksum += $helper->compareProductsChecksum($iter, self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION, $store_id);
                $iter += self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION;
            }
        } else {
            // start updating the checksum table if needed
            $num_of_updated_checksum = $helper->compareProductsChecksum($start_index, $count, $store_id);
        }

        $process_time = time() - $start_time;
        // sending confirmation/summary to the server
        $args = array(  'uuid' 			      => $uuid,
            'site_url' 			  => $site_url,
            'store_id'            => $store_id,
            'updated_checksum' 	  => $num_of_updated_checksum,
            'total_checksum' 	  => $count,
            'num_of_products'     => $num_of_products,
            'start_index'		  => $start_index,
            'end_index'	          => $start_index + $count,
            'count'               => $count,
            'ext_version'	      => (string)Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version,
            'mage_version'        => Mage::getVersion(),
            'latency'             => $process_time,         // seconds
        );
        if ($is_single)
            $args['is_single'] = 1;
        echo json_encode($args);    // returning the summary

        $resp = $helper->sendCurl($checksum_server . '/magento_checksum_iterator?' . http_build_query($args));

        if ($max_exe_time != -1){   // restore php max execution time
            ini_set('max_execution_time', $max_exe_time);
        }
    }

    public function connectionAction(){
        exit('1');
    }

    public function changeSerpAction(){
        try {
            $helper = Mage::helper('autocompleteplus_autosuggest');
            $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
            define("SOAP_WSDL", $site_url . '/api/?wsdl');
            define("SOAP_USER", "instant_search");
            define("SOAP_PASS", "Rilb@kped3");

            $client = new SoapClient(SOAP_WSDL, array('trace' => 1, 'cache_wsdl' => 0));
            $session = $client->login(SOAP_USER, SOAP_PASS);

            $post = $this->getRequest()->getParams();
            if (array_key_exists('new_serp', $post)){
                $is_new_serp = $post['new_serp'];
            }else{
                $is_new_serp = '0';      // default
            }

            if (array_key_exists('store_id', $post)){
                $store_id = $post['store_id'];
                $scope_name = 'stores';
            }else{   // default
                $store_id = '0';
                $scope_name = 'default';
            }

            try {

                if ($is_new_serp == 'status'){
                    $current_state = $client->call($session, 'autocompleteplus_autosuggest.getLayeredSearchConfig', array($store_id));
                    echo json_encode(array('current_status' => $current_state));
                    return;
                }

                if ($is_new_serp == '1'){
                    $status = $client->call($session, 'autocompleteplus_autosuggest.setLayeredSearchOn', array($scope_name, $store_id));
                } else {
                    $status = $client->call($session, 'autocompleteplus_autosuggest.setLayeredSearchOff', array($scope_name, $store_id));
                }
                $new_state= $client->call($session, 'autocompleteplus_autosuggest.getLayeredSearchConfig', array($store_id));

                $resp = array('request_state' 	     => $is_new_serp,
                    'new_state'            => $new_state,
                    'site_url' 			 => $site_url,
                    'status'               => $status
                );
                echo json_encode($resp);

            } catch (SoapFault $exception) {
                echo json_encode(array('status' => 'exception: ' . print_r($exception, true)));
                return;
            }

        } catch (Exception $e){
            echo json_encode(array('status' => 'exception: ' . print_r($e, true)));
            throw $e;
        }
    }

    public function pushbulkAction(){

        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

//        $enabled= Mage::getStoreConfig('autocompleteplus/config/enabled');
//        if($enabled=='0'){
//            die('The user has disabled autocompleteplus.');
//        }

        $helper = Mage::helper('autocompleteplus_autosuggest');

        if(!isset($post['pushid'])){

            echo json_encode(array('success'=>false,'message'=>'Missing pushid!'));
            die;
        }

        $pushid = $post['pushid'];

        $pusher = Mage::getModel('autocompleteplus_autosuggest/pusher')->load($pushid);

        $sent=$pusher->getSent();

        if($sent==1){
            echo json_encode(array('success'=>false,'message'=>'push is in process'));
            die;
        }elseif($sent==2){
            echo json_encode(array('success'=>false,'message'=>'push was already sent'));
            die;
        }else{
            $pusher->setSent(1);

            $pusher->save();
        }

        $offset     = $pusher->getoffset();

        $count        = 100;

        $storeId=$pusher->getstore_id();

        $to_send = $pusher->getto_send();

        $total_batches = $pusher->gettotal_batches();

        $catalogModel=Mage::getModel('autocompleteplus_autosuggest/catalog');

        $xml=$catalogModel->renderCatalogXml($offset,$count,$storeId,'','','');

        $url=$helper->getConfigDataByFullPath('web/unsecure/base_url');

        // setting post data and command url
        $data['uuid'] = $helper->getUUID();
        $data['site_url'] = $url;
        $data['store_id'] = $storeId;
        $data['authentication_key'] = $helper->getKey();
        $data['total_batches'] = $total_batches;
        $data['batch_number'] = $pusher->getbatch_number();

        if ($offset+$count > $to_send) {
            $data['is_last'] = 1;

            $count=$to_send-$offset;
            // error_log('IS LAST ' . $data['is_last']);
        }

        $data['products'] =  $xml;

        $server_url = $helper->getServerUrl();
        $cmd_url = $server_url . '/magento_fetch_products';

        // sending products
        $res2 = $helper->sendPostCurl($cmd_url, $data);

        unset($data['products']);

        Mage::log(print_r($data,true), null, 'autocomplete.log',true);
        Mage::log(print_r($res2,true), null, 'autocomplete.log',true);

        if($res2=='ok'){
            $pusher->setSent(2);

            $pusher->save();

            $nextPushId=$helper->getPushId();

            $nextPushUrl='';

            if($nextPushId!=''){
                $nextPushUrl=$helper->getPushUrl($nextPushId);
            }

            $totalPushes= Mage::getModel('autocompleteplus_autosuggest/pusher')->getCollection()->count();

            $updatedStatus='Syncing: push '.$nextPushId.'/'.$totalPushes;

            $updatedSuccessStatus='Successfully synced '.$count.' products';

            echo json_encode(
                array(
                    'success'=>true,
                    'updatedStatus'=>$updatedStatus,
                    'updatedSuccessStatus'=>$updatedSuccessStatus,
                    'message'=>'',
                    'nextPushUrl'=>$nextPushUrl,
                    'count'=>$count
                )
            );

            die;
        }else{
            echo json_encode(array('success'=>false,'message'=>$res2));
            die;
        }
    }

}
