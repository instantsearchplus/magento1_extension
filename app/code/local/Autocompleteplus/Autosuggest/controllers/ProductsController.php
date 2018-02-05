<?php
/**
 * ProductsController File
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
 * Autocompleteplus_Autosuggest_ProductsController
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
class Autocompleteplus_Autosuggest_ProductsController extends Autocompleteplus_Autosuggest_Controller_Abstract
{
    const MISSING_PARAMETER = 'false';
    const PUSH_IN_PROGRESS = 1;
    const PUSH_COMPLETE = 2;
    const POST_MESSAGE_OK = 'ok';
    const URL_EMAIL_UPDATE = 'http://magento.autocompleteplus.com/ext_update_email';
    const URL_UUID_UPDATE = 'http://magento.instantsearchplus.com/update_uuid';
    const XML_CONFIG_STORE_EMAIL = 'autocompleteplus/config/store_email';

    /**
     * Get ext config
     *
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    /**
     * Returns products batch
     *
     * @return void
     */
    public function sendAction()
    {
        Varien_Profiler::start('Autocompleteplus_Autosuggest_Products_Send');
        $response = $this->getResponse();
        $request = $this->getRequest();
        $startInd = $request->getParam('offset', 0);
        $count = $request->getParam('count', 100);
        $store = $request->getParam('store_id', '');
        $storeId = $request->getParam('store', $store);
        $orders = $request->getParam('orders', '');
        $monthInterval = $request->getParam('month_interval', 12);
        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');

        Mage::app()->setCurrentStore($storeId);

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $xml = $catalogModel->renderCatalogXml(
            $startInd,
            $count,
            $storeId,
            $orders,
            $monthInterval
        );
        $response->setBody($xml);
        Varien_Profiler::stop('Autocompleteplus_Autosuggest_Products_Send');
    }

    /**
     * Returns updated products batch
     *
     * @return void
     */
    public function sendupdatedAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $currentTime = Mage::getSingleton('core/date')->gmtTimestamp();

        $count = $request->getParam('count');
        $from = $request->getParam('from');
        $to = $request->getParam('to', $currentTime);
        $storeId = $request->getParam('store_id', false);

        if (!$storeId) {
            $returnArr = array(
                'status' => self::STATUS_FAILURE,
                'error_code' => self::MISSING_PARAMETER,
                'error_details' => $this->__(
                    'The "store id" parameter is mandatory'
                ),
            );
            $response->setHeader('Content-type', 'application/json');
            $response->setHttpResponseCode(400);
            $response->setBody(json_encode($returnArr));

            return;
        }

        Mage::app()->setCurrentStore($storeId);

        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        
        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $xml = $catalogModel->renderUpdatesCatalogXml($count, $from, $to, $storeId);
        $response->setBody($xml);
    }

    /**
     * Checks install
     *
     * @return void
     */
    public function checkinstallAction()
    {
        $response = $this->getResponse();
        $installStatus = $this->_getInstallStatus();

        $response->setBody($installStatus);
    }

    /**
     * Returns install status
     *
     * @return string
     */
    protected function _getInstallStatus()
    {
        $uuid = $this->_getConfig()->getUUID();

        if (strlen($uuid) > 0 && $uuid != 'failed') {
            return $this->__('the key exists');
        }

        return $this->__('no key inside');
    }

    /**
    * Returns version info json
    *
    * @return void
    */
    public function versAction()
    {
        $response = $this->getResponse();
        $get_modules = $this->getRequest()->getParam('modules', false);
        $mage = Mage::getVersion();
        $ext = Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $edition = method_exists('Mage', 'getEdition') ?
            Mage::getEdition() : 'Community';
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $uuid = $this->_getConfig()->getUUID();
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $store_id = Mage::app()->getStore()->getStoreId();
        $installedModules = array();

        $enabled = Mage::getStoreConfigFlag('autocompleteplus/config/enabled', 0);

        $flatProductsEnabled = Mage::getStoreConfigFlag(
            'catalog/frontend/flat_catalog_product', 0
        );

        $flatCategoriesEnabled = Mage::getStoreConfigFlag(
            'catalog/frontend/flat_catalog_category', 0
        );

        if (defined('COMPILER_INCLUDE_PATH')) {
            $compilerEnabled = true;
        } else {
            $compilerEnabled = false;
        }

        try {
            $num_of_products = Mage::getModel('catalog/product')->getCollection()
                ->addStoreFilter($store_id)
                ->getSize();
        } catch (Exception $e) {
            $num_of_products = -1;
        }

        if ($get_modules) {
            try {
                $modules = Mage::getConfig()->getNode('modules')->children();
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
            'enabled' => $enabled,
            'flat_products_enabled' => $flatProductsEnabled,
            'flat_categories_enabled' => $flatCategoriesEnabled,
            'compiler_enabled' => $compilerEnabled
        );

        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode($result));
    }

    /**
     * Returns number of products
     *
     * @return string
     */
    public function getNumOfProductsAction()
    {
        $catalogReport = Mage::getModel(
            'autocompleteplus_autosuggest/catalogreport'
        );
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $result = array(
            'num_of_products' => $catalogReport->getEnabledProductsCount(),
            'num_of_disabled_products' => $catalogReport->getDisabledProductsCount(),
            'num_of_searchable_products' => $catalogReport
                ->getSearchableProductsCount(),
            'num_of_searchable_products2' => $catalogReport
                ->getSearchableProducts2Count(),
            'uuid' => $this->_getConfig()->getUUID(),
            'site_url' => $helper->getConfigDataByFullPath('web/unsecure/base_url'),
            'store_id' => $catalogReport->getCurrentStoreId(),
            );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * Returns conflicts json
     *
     * @return void
     */
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

    /**
     * Returns orders json
     *
     * @return void
     */
    public function getstoresAction()
    {
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($helper->getMultiStoreDataJson());
    }

    /**
     * Returns robots path
     *
     * @return string
     */
    protected function _getRobotsPath()
    {
        if (!$this->_robotsPath) {
            $this->_robotsPath = Mage::getBaseDir().DS.'robots.txt';
        }

        return $this->_robotsPath;
    }

    /**
     * Updates sitemap
     *
     * @return void
     */
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
                if (strpos($io->read($robotsPath), $sitemapUrl) === false) {
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
                    $data['msg'] = $this->__(
                        'Directory %s is not writable.', Mage::getBaseDir()
                    );
                    $res = $helper->sendPostCurl($command, $data);
                }
            }

            if ($write) {
                if ($io->isWritable($robotsPath)) {
                    /**
                     * Append sitemap
                     */
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

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return string
     */
    protected function _setUUID($uuid)
    {
        $this->_getConfig()->setUUID($uuid);
    }

    /**
     * Returns uuid
     *
     * @return void
     */
    public function getIspUuidAction()
    {
        $response = $this->getResponse();
        $response->setBody($this->_getConfig()->getUUID());
    }

    /**
     * Get error message
     *
     * @return void
     */
    public function geterrormessageAction()
    {
        $response = $this->getResponse();
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $response->setBody($helper->getErrormessage());
    }

    /**
     * Sets uuid
     *
     * @return void
     */
    public function setIspUuidAction()
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $url_domain = self::URL_UUID_UPDATE;
        $storeId = Mage::app()->getStore()->getStoreId();
        $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');

        $url = $url_domain.http_build_query(
                array(
                        'store_id' => $storeId,
                        'site_url' => $site_url,
                     )
        );

        $helper = Mage::helper('autocompleteplus_autosuggest');
        $resp = $helper->sendCurl($url);
        $response_json = json_decode($resp);

        if ($helper->validateUuid($response_json->uuid)) {
            $this->_setUUID($response_json->uuid);
        }
    }

    /**
     * Checks connection
     *
     * @return void
     */
    public function connectionAction()
    {
        $this->getResponse()->setBody(1);
    }

    /**
     * Bulk Push to ISP with JSON.
     *
     * @return void
     */
    public function pushbulkAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');
        $pushId = $request->getParam('pushid', null);
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $data = array();

        if (!isset($pushId)) {
            $responseArr = array('success' => false, 'message' => 'Missing pushid!');
            $response->clearHeaders();
            $response->setHeader('Content-type', 'application/json');
            $response->setBody(json_encode($responseArr));

            return;
        }

        $pusher = Mage::getModel('autocompleteplus_autosuggest/pusher')
            ->load($pushId);
        $sent = $pusher->getSent();

        if ($sent == 1) {
            $responseArr = array(
                'success' => false,
                'message' => 'push is in process'
            );
            $response->setBody(json_encode($responseArr));

            return;
        } elseif ($sent == 2) {
            $responseArr = array(
                'success' => false,
                'message' => 'push was already sent'
            );
            $response->setBody(json_encode($responseArr));

            return;
        } else {
            $pusher->setSent(1);
            $pusher->save();
        }

        $offset = $pusher->getoffset();
        $count = 100;
        $storeId = $pusher->getstore_id();
        $to_send = $pusher->getto_send();
        $total_batches = $pusher->gettotal_batches();
        $catalogModel = Mage::getModel('autocompleteplus_autosuggest/catalog');
        $url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
        $server_url = $helper->getServerUrl();
        $cmd_url = $server_url.'/magento_fetch_products';

        // setting post data and command url
        $data['uuid'] = $helper->getUUID();
        $data['site_url'] = $url;
        $data['store_id'] = $storeId;
        $data['authentication_key'] = $helper->getKey();
        $data['total_batches'] = $total_batches;
        $data['batch_number'] = $pusher->getbatch_number();
        $data['products'] = $catalogModel
            ->renderCatalogXml($offset, $count, $storeId, '', '', '');

        if ($offset + $count > $to_send) {
            $data['is_last'] = 1;
            $count = $to_send - $offset;
        }

        // sending products
        $res2 = $helper->sendPostCurl($cmd_url, $data);
        unset($data['products']);

        if ($res2 !== 'ok') {
            $responseArr = array('success' => false, 'message' => $res2);
            $response->setBody($responseArr);

            return;
        }

        $pusher->setSent(2);
        $pusher->save();

        $nextPushId = $helper->getPushId();
        $nextPushUrl = '';

        if ($nextPushId != '') {
            $nextPushUrl = $helper->getPushUrl($nextPushId);
        }

        $totalPushes = Mage::getModel('autocompleteplus_autosuggest/pusher')
            ->getCollection()
            ->getSize();

        $updatedStatus = 'Syncing: push '.$nextPushId.'/'.$totalPushes;
        $updatedSuccessStatus = 'Successfully synced '.$count.' products';

        $responseArr = array(
            'success' => true,
            'updatedStatus' => $updatedStatus,
            'updatedSuccessStatus' => $updatedSuccessStatus,
            'message' => '',
            'nextPushUrl' => $nextPushUrl,
            'count' => $count,
        );

        $response->setBody(json_encode($responseArr));
    }
}
