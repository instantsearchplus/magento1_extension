<?php
/**
 * InstantSearchPlus (Autosuggest).
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 *
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Autocompleteplus_Autosuggest_ProductsController extends Autocompleteplus_Autosuggest_Controller_Abstract
{
    protected $_storeId;
    const MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION = 250;
    const MISSING_PARAMETER = 'false';
    const PUSH_IN_PROGRESS = 1;
    const PUSH_COMPLETE = 2;
    const POST_MESSAGE_OK = 'ok';
    const URL_EMAIL_UPDATE = 'http://magento.autocompleteplus.com/ext_update_email';
    const URL_UUID_UPDATE = 'http://magento.instantsearchplus.com/update_uuid';
    const XML_CONFIG_STORE_EMAIL = 'autocompleteplus/config/store_email';

    protected function _getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    public function sendAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $startInd = $request->getParam('offset');
        $count = $request->getParam('count');
        $store = $request->getParam('store_id', '');
        $storeId = $request->getParam('store', $store);
        $orders = $request->getParam('orders', '');
        $monthInterval = $request->getParam('month_interval', '');
        $checksum = $request->getParam('checksum', '');
        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');

        $xml = $catalogModel->renderCatalogXml($startInd, $count, $storeId, $orders, $monthInterval, $checksum);

        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }

    public function sendupdatedAction()
    {
        date_default_timezone_set('Asia/Jerusalem');

        $request = $this->getRequest();
        $response = $this->getResponse();
        $count = $request->getParam('count');
        $from = $request->getParam('from');
        $to = $request->getParam('to', strtotime('now'));
        $storeId = $request->getParam('store_id', '');

        if (!$storeId) {
            $returnArr = array(
                'status' => self::STATUS_FAILURE,
                'error_code' => self::MISSING_PARAMETER,
                'error_details' => $this->__('The "store id" parameter is mandatory'),
            );
            $response->setHeader('Content-type', 'application/json');
            $response->setHttpResponseCode(400);
            $response->setBody(json_encode($returnArr));

            return;
        }

        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');

        $xml = $catalogModel->renderUpdatesCatalogXml($count, $from, $to, $storeId);

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }

    public function checkinstallAction()
    {
        $response = $this->getResponse();
        $installStatus = $this->_getInstallStatus();

        $response->setBody($installStatus);
    }

    protected function _getInstallStatus()
    {
        if (strlen($this->_getConfig()->getUUID()) > 0 && $this->_getConfig()->getUUID() != 'failed') {
            return $this->__('the key exists');
        }

        return $this->__('no key inside');
    }

    public function versAction()
    {
        $response = $this->getResponse();
        $get_modules = $this->getRequest()->getParam('modules', false);
        $mage = Mage::getVersion();
        $ext = Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $edition = method_exists('Mage', 'getEdition') ? Mage::getEdition() : 'Community';
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $uuid = $this->_getConfig()->getUUID();
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $store_id = Mage::app()->getStore()->getStoreId();
        $modules = Mage::getConfig()->getNode('modules')->children();
        $installedModules = array();

        try {
            $num_of_products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($store_id)
                ->getSize();
        } catch (Exception $e) {
            $num_of_products = -1;
        }

        if ($get_modules) {
            try {
                foreach ($modules as $name => $module) {
                    if ($module->codePool != 'core' && $module->active == 'true') {
                        $installedModules[$name] = $module;
                    }
                }
            } catch (Exception $e) {
                $installedModules = array();
            }
        }

        $result = array(
            'mage' => $mage,
            'ext' => $ext,
            'num_of_products' => $num_of_products,
            'edition' => $edition,
            'uuid' => $uuid,
            'site_url' => $site_url,
            'store_id' => $store_id,
            'modules' => $installedModules,
        );

        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode($result));
    }

    public function getNumOfProductsAction()
    {
        $catalogReport = Mage::getModel('autocompleteplus_autosuggest/catalogreport');
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $result = array('num_of_products' => $catalogReport->getEnabledProductsCount(),
                        'num_of_disabled_products' => $catalogReport->getDisabledProductsCount(),
                        'num_of_searchable_products' => $catalogReport->getSearchableProductsCount(),
                        'num_of_searchable_products2' => $catalogReport->getSearchableProducts2Count(),
                        'uuid' => $this->_getConfig()->getUUID(),
                        'site_url' => $helper->getConfigDataByFullPath('web/unsecure/base_url'),
                        'store_id' => $catalogReport->getCurrentStoreId(),
        );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

    public function getConflictAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        //check for extension conflicts
        $conflicts = (bool) $request->getParam('all');
        $result = $helper->getExtensionConflict($conflicts);

        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode($result));
    }

    public function getstoresAction()
    {
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $response->setBody($helper->getMultiStoreDataJson());
    }

    protected function _getRobotsPath()
    {
        if (!$this->_robotsPath) {
            $this->_robotsPath = Mage::getBaseDir().DS.'robots.txt';
        }

        return $this->_robotsPath;
    }

    public function updatesitemapAction()
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $key = $this->_getConfig()->getUUID();
        $url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $robotsPath = $this->_getRobotsPath();
        $io = new Varien_Io_File();
        $io->open(array('path' => $io->dirName($robotsPath)));

        if ($this->validateUuid($key)) {
            $sitemapUrl = 'Sitemap:http://magento.instantsearchplus.com/ext_sitemap?u='.$key;
            $write = false;

            if ($io->fileExists($robotsPath)) {
                if (strpos($io->read($robotsPath), $sitemapUrl) == false) {
                    $write = true;
                }
            } else {
                if ($io->isWritable(Mage::getBaseDir())) {

                    //create robots sitemap
                    $io->write($robotsPath, $sitemapUrl);
                } else {

                    //write message that directory is not writteble
                    $command = 'http://magento.autocompleteplus.com/install_error';

                    $data = array();
                    $data['site'] = $url;
                    $data['msg'] = $this->__('Directory %s is not writable.', Mage::getBaseDir());
                    $res = $helper->sendPostCurl($command, $data);
                }
            }

            if ($write) {
                if ($io->isWritable($robotsPath)) {
                    //append sitemap
                    $io->write($robotsPath, $sitemapUrl, FILE_APPEND | LOCK_EX);
                } else {
                    //write message that file is not writteble
                    $command = 'http://magento.autocompleteplus.com/install_error';

                    $data = array();
                    $data['site'] = $url;
                    $data['msg'] = 'File '.$robotsPath.' is not writable.';
                    $res = $helper->sendPostCurl($command, $data);
                }
            }
        }
    }

    protected function _setUUID($uuid)
    {
        $this->_getConfig()->setUUID($uuid);
    }

    public function getIspUuidAction()
    {
        $response = $this->getResponse();
        $response->setBody($this->_getConfig()->getUUID());
    }

    public function geterrormessageAction()
    {
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $response->setBody($helper->getErrormessage());
    }

    public function setIspUuidAction()
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $url_domain = self::URL_UUID_UPDATE;
        $storeId = Mage::app()->getStore()->getStoreId();
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

        $url = $url_domain.http_build_query(array(
            'store_id' => $storeId,
            'site_url' => $site_url,
        ));

        $helper = Mage::helper('autocompleteplus_autosuggest');
        $resp = $helper->sendCurl($url);
        $response_json = json_decode($resp);

        if ($helper->validateUuid($response_json->uuid)) {
            $this->_setUUID($response_json->uuid);
        }
    }

    public function checkDeletedAction()
    {
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');
        if (!$helper->isChecksumTableExists()) {
            return;
        }
        $time_stamp = time();

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table_prefix = (string) Mage::getConfig()->getTablePrefix();

        $post = $this->getRequest()->getParams();
        if (array_key_exists('store_id', $post)) {
            $store_id = $post['store_id'];
        } else {
            $store_id = Mage::app()->getStore()->getStoreId();          // default
        }

        $sql_fetch = 'SELECT identifier FROM '.$table_prefix.'autocompleteplus_checksum WHERE store_id=?';
        $updates = $read->fetchPairs($sql_fetch, array($store_id));     // empty array if fails
        if (empty($updates)) {
            return;
        }

        $checksum_ids = array_keys($updates);   // array of all checksum table identifiers        
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addFieldToFilter('entity_id', array('in' => $checksum_ids));
        $found_ids = $collection->getAllIds();

        $removed_products_list = array_diff($checksum_ids, $found_ids);     // list of identifiers that are not present in the store (removed at some point)
        $removed_ids = array();

        // removing non-existing identifiers from checksum table
        if (!empty($removed_products_list)) {
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql_delete = 'DELETE FROM '.$table_prefix.'autocompleteplus_checksum WHERE identifier IN ('.implode(',', $removed_products_list).')';
            $write->query($sql_delete);

            foreach ($removed_products_list as $product_id) {
                $helper->deleteProductFromTables($read, $write, $table_prefix, $product_id, $store_id);
                $removed_ids[] = $product_id;
            }
        }

        $args = array('removed_ids' => $removed_ids,
            'uuid' => $this->_getConfig()->getUUID(),
            'store_id' => $store_id,
            'latency' => time() - $time_stamp,         // seconds
        );

        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode($args));    // returning the summary
    }

    public function checksumAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $store_id = $request->getParam('store_id', Mage::app()->getStore()->getStoreId());
        $count = $request->getParam('count', self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION);
        $start_index = $request->getParam('offset', 0);
        $php_timeout = $request->getParam('timeout', -1);
        $is_single = $request->getParam('is_single', 0);
        $uuid = $this->_getConfig()->getUUID();
        $checksum_server = $helper->getServerUrl();
        $collection = Mage::getModel('catalog/product')->getCollection();

        if (!$helper->isChecksumTableExists()) {
            $helper->ispErrorLog('checksum table not exist');
            $response->setBody(json_encode(array('status' => 'checksum table not exist')));

            return;
        }

        $max_exe_time = -1;

        if ($count > self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION && $php_timeout != -1) {
            $max_exe_time = ini_get('max_execution_time');
            ini_set('max_execution_time', $php_timeout);
        }

        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

        if ($store_id) {
            $collection->addStoreFilter($store_id);
        }

        $num_of_products = $collection->getSize();

        if ($count + $start_index > $num_of_products) {
            $count = $num_of_products - $start_index;
        }

        // sending log to the server        
        $log_msg = 'Update checksum is starting...';
        $log_msg .= (' number of products in this store: '.$num_of_products.' | from: '.$start_index.', to: '.($start_index + $count));
        $server_url = $checksum_server.'/magento_logging_record';
        $request = $server_url.'?uuid='.$uuid.'&site_url='.$site_url.'&msg='.urlencode($log_msg);
        if ($store_id) {
            $request .= '&store_id='.$store_id;
        }
        $resp = $helper->sendCurl($request);

        $start_time = time();
        $num_of_updated_checksum = 0;
        if ($count > self::MAX_NUM_OF_PRODUCTS_CHECKSUM_ITERATION) {
            $iter = $start_index;
            while ($iter < $count) {
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
        $args = array(
            'uuid' => $uuid,
            'site_url' => $site_url,
            'store_id' => $store_id,
            'updated_checksum' => $num_of_updated_checksum,
            'total_checksum' => $count,
            'num_of_products' => $num_of_products,
            'start_index' => $start_index,
            'end_index' => $start_index + $count,
            'count' => $count,
            'ext_version' => (string) Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version,
            'mage_version' => Mage::getVersion(),
            'latency' => $process_time,
        );
        if ($is_single) {
            $args['is_single'] = 1;
        }

        $response->setBody(json_encode($args));

        $resp = $helper->sendCurl($checksum_server.'/magento_checksum_iterator?'.http_build_query($args));

        if ($max_exe_time != -1) {   // restore php max execution time
            ini_set('max_execution_time', $max_exe_time);
        }
    }

    public function connectionAction()
    {
        $this->getResponse()->setBody(1);
    }

    public function changeSerpAction()
    {
        $scope_name = 'stores';
        $request = $this->getRequest();
        $response = $this->getResponse();

        $helper = Mage::helper('autocompleteplus_autosuggest');
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $is_new_serp = $request->getParam('new_serp', 0);

        $store_id = $request->getParam('store_id', 0);
        if (!$store_id) {
            $scope_name = 'default';
        }

        define('SOAP_WSDL', $site_url.'/api/?wsdl');
        define('SOAP_USER', 'instant_search');
        define('SOAP_PASS', 'Rilb@kped3');

        try {
            $client = new SoapClient(SOAP_WSDL, array('trace' => 1, 'cache_wsdl' => 0));
            $session = $client->login(SOAP_USER, SOAP_PASS);

            switch ($is_new_serp) {

                case 'status':
                    $current_state = $client->call($session, 'autocompleteplus_autosuggest.getLayeredSearchConfig', array($store_id));
                    $resp = array('current_status' => $current_state);
                    $response->setBody(json_encode($resp));

                    return;

                case '1':
                    $status = $client->call($session, 'autocompleteplus_autosuggest.setLayeredSearchOn', array($scope_name, $store_id));
                    break;
                default:
                    $status = $client->call($session, 'autocompleteplus_autosuggest.setLayeredSearchOff', array($scope_name, $store_id));
                    break;
            }

            $new_state = $client->call($session, 'autocompleteplus_autosuggest.getLayeredSearchConfig', array($store_id));

            $resp = array(
                'request_state' => $is_new_serp,
                'new_state' => $new_state,
                'site_url' => $site_url,
                'status' => $status,
            );

            $response->setBody(json_encode($resp));
        } catch (Exception $e) {
            $resp = array('status' => 'exception: '.print_r($e, true));
            $response->setBody(json_encode($resp));
            Mage::logException($e);
            throw $e;
        }
    }

    public function pushbulkAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $pushId = $request->getParam('pushid', null);
        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        $count = 100;
        $server_url = $helper->getServerUrl();
        $cmd_url = $server_url.'/magento_fetch_products';

        if (!$pushId) {
            $returnArr = array(
                'success' => self::MISSING_PARAMETER,
                'message' => $this->__('Missing push id!'),
            );

            $response->setHttpResponseCode(400);
            $response->setBody(json_encode($returnArr));

            return;
        }

        $pusher = Mage::getModel('autocompleteplus_autosuggest/pusher')->load($pushid);
        $sent = $pusher->getSent();

        switch ($sent) {
            case self::PUSH_IN_PROGRESS:
                $responseArr = array('success' => false, 'message' => $this->__('push is in process'));
                $response->setBody(json_encode($responseArr));

                return;
            case self::PUSH_COMPLETE:
                $responseArr = array('success' => false, 'message' => $this->__('push was already sent'));
                $response->setBody(json_encode($responseArr));

                return;
            default:
                $pusher->setSent(1)->save();
                break;
        }

        $offset = $pusher->getoffset();
        $storeId = $pusher->getstore_id();
        $to_send = $pusher->getto_send();
        $total_batches = $pusher->gettotal_batches();

        $xml = $catalogModel->renderCatalogXml($offset, $count, $storeId, '', '', '');

        $url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

        // setting post data and command url
        $data = array(
            'uuid' => $this->_getConfig()->getUUID(),
            'site_url' => $url,
            'store_id' => $storeId,
            'authentication_key' => $this->_getConfig()->getAuthenticationKey(),
            'total_batches' => $total_batches,
            'batch_number' => $pusher->getbatch_number(),
            'products' => $xml,
        );

        if ($offset + $count > $to_send) {
            $data['is_last'] = 1;
            $count = $to_send - $offset;
        }

        // sending products
        $postResponseMessage = $helper->sendPostCurl($cmd_url, $data);

        if ($postResponseMessage != self::POST_MESSAGE_OK) {
            $responseArr = array(
                'success' => false,
                'message' => $postResponseMessage,
            );

            $response->setBody(json_encode($responseArr));

            return;
        }

        $pusher->setSent(2)->save();
        $nextPushId = $helper->getPushId();

        $totalPushes = Mage::getModel('autocompleteplus_autosuggest/pusher')->getCollection()->getSize();

        $responseArr = array(
            'success' => true,
            'updatedStatus' => $this->__('Syncing: push %s/%s', $nextPushId, $totalPushes),
            'updatedSuccessStatus' => $this->__('Successfully synced %s products', $count),
            'message' => '',
            'nextPushUrl' => $nextPushId ? $helper->getPushUrl($nextPushId) : '',
            'count' => $count,
        );

        $response->setBody(json_encode($responseArr));
    }
}
