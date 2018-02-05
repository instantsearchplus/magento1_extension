<?php
/**
 * Data File
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
 * Autocompleteplus_Autosuggest_Helper_Data
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
class Autocompleteplus_Autosuggest_Helper_Data extends Mage_Core_Helper_Abstract
{
    const WEBSITES_SCOPE = 'websites';

    const STORES_SCOPE = 'stores';

    const DEFAULT_SCOPE = 'default';

    const WEBSITE_ID = 'website_id';

    protected $server_url = 'http://magento.instantsearchplus.com';

    /**
     * Return server url
     *
     * @return string
     */
    public function getServerUrl()
    {
        return $this->server_url;
    }

    /**
     * Validate uuid
     *
     * @param string $uuid comment
     *
     * @return bool
     */
    public function validateUuid($uuid)
    {
        if (strlen($uuid) == 36
            && substr_count($uuid, '-') == 4
        ) {
            return true;
        }

        return false;
    }

    /**
     * Return extension config model
     *
     * @return false|Mage_Core_Model_Abstract
     */
    public function getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    /**
     * Return Magento version
     *
     * @return string
     */
    public function getMageVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Return Extension version
     *
     * @return string
     */
    public function getVersion()
    {
        return (string) Mage::getConfig()
            ->getModuleConfig('Autocompleteplus_Autosuggest')
            ->version;
    }

    /**
     * Get data from magento config by path
     *
     * @param string $path comment
     *
     * @return mixed|string
     */
    public function getConfigDataByFullPath($path)
    {
        $valsArr = $this->getConfigMultiDataByFullPath($path);

        $value = '';

        if (is_array($valsArr) && count($valsArr) > 0) {
            $value = array_shift($valsArr);
        }

        return $value;
    }

    /**
     * Get multi line data from magento config by path
     *
     * @param string $path comment
     *
     * @return mixed|string
     */
    public function getConfigMultiDataByFullPath($path)
    {
        $values = array();

        $rows = Mage::getSingleton('core/config_data')
            ->getCollection()
            ->getItemsByColumnValue('path', $path);

        if (!$rows) {
            $conf = Mage::getSingleton('core/config')
                ->init()
                ->getXpath('/config/default/'.$path);
            $values[] = array_shift($conf);

        } else {
            foreach ($rows as $row) {
                $scopeId = $row->getScopeId();

                $rowValue = $row->getValue();

                if ($scopeId != null && $rowValue != null) {
                    $values[$scopeId] = $rowValue;
                }
            }
        }

        return $values;
    }

    /**
     * Get multi line data by scopes
     * from magento config by path
     *
     * @param string $path comment
     *
     * @return mixed|string
     */
    public function getConfigMultiScopesDataByFullPath($path)
    {
        $values = array();

        $rows = Mage::getSingleton('core/config_data')
            ->getCollection()
            ->getItemsByColumnValue('path', $path);

        foreach ($rows as $row) {
            $scope = $row->getScope();

            $scopeId = $row->getScopeId();

            $rowValue = $row->getValue();

            if ($scope != null && $scopeId != null && $rowValue != null) {
                if (!array_key_exists($scope, $values)) {
                    $values[$scope] = array();
                }

                $values[$scope][$scopeId] = $rowValue;
            }
        }

        return $values;
    }

    /**
     * Send curl request
     *
     * @param string $command comment
     *
     * @return mixed|string
     */
    public function sendCurl($command)
    {
        if (isset($ch)) {
            unset($ch);
        }

        if (function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            $str = curl_exec($ch);
        } else {
            $str = 'failed';
        }

        return $str;
    }

    /**
     * Send curl POST request
     *
     * @param string $command     comment
     * @param array  $data        comment
     * @param string $cookie_file comment
     *
     * @return mixed|string
     */
    public static function sendPostCurl(
        $command,
        $data = array(),
        $cookie_file = 'genCookie.txt'
    ) {
        if (isset($ch)) {
            unset($ch);
        }

        if (function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt(
                $ch,
                CURLOPT_USERAGENT,
                'Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20100101 Firefox/21.0'
            );
            //curl_setopt($ch,CURLOPT_POST,0);
            if (!empty($data)) {
                curl_setopt_array(
                    $ch,
                    array(
                    CURLOPT_POSTFIELDS => $data,
                    )
                );
            }

            /**
             * Setting Http Header
             * curl_setopt($ch, CURLOPT_HTTPHEADER, array(
             * 'Connection: Keep-Alive',
             * 'Keep-Alive: 800'
             * ));
             */


            $str = curl_exec($ch);
        } else {
            $str = 'failed';
        }

        return $str;
    }

    /**
     * Prepare grouped product price
     *
     * @param mixed $groupedProduct comment
     *
     * @return void
     */
    public function prepareGroupedProductPrice($groupedProduct)
    {
        $aProductIds = $groupedProduct
            ->getTypeInstance()
            ->getChildrenIds($groupedProduct->getId());

        $prices = array();
        foreach ($aProductIds as $ids) {
            foreach ($ids as $id) {
                try {
                    $aProduct = Mage::getModel('catalog/product')->load($id);
                    $prices[] = $aProduct->getPriceModel()->getPrice($aProduct);
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        krsort($prices);
        try {
            if (count($prices) > 0) {
                $groupedProduct->setPrice($prices[0]);
            } else {
                $groupedProduct->setPrice(0);
            }
        } catch (Exception $e) {
            $groupedProduct->setPrice(0);
        }

        /**
         * Or you can return price
         */
    }

    /**
     * Get bundled product price
     *
     * @param mixed $product comment
     *
     * @return float
     */
    public function getBundlePrice($product)
    {
        $optionCol = $product->getTypeInstance(true)
            ->getOptionsCollection($product);
        $selectionCol = $product->getTypeInstance(true)
            ->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );
        $optionCol->appendSelections($selectionCol);
        $price = $product->getPrice();

        foreach ($optionCol as $option) {
            if ($option->required) {
                $selections = $option->getSelections();
                $selPricesArr = array();

                if (is_array($selections)) {
                    foreach ($selections as $s) {
                        $selPricesArr[] = $s->price;
                    }

                    $minPrice = min($selPricesArr);

                    if ($product->getSpecialPrice() > 0) {
                        $minPrice *= $product->getSpecialPrice() / 100;
                    }

                    $price += round($minPrice, 2);
                }
            }
        }

        return $price;
    }

    /**
     * Get multi stores json
     *
     * @return string
     */
    public function getMultiStoreDataJson()
    {
        $websites = Mage::getModel('core/website')->getCollection();
        $mage = Mage::getVersion();
        $ext = Mage::helper('autocompleteplus_autosuggest')->getVersion();
        $version = array('mage' => $mage, 'ext' => $ext);

        /**
         * Getting site url
         */
        $url = $this->getConfigDataByFullPath('web/unsecure/base_url');

        /**
         * Getting site owner email
         */
        $storeMail = $this->getConfigDataByFullPath(
            'autocompleteplus/config/store_email'
        );

        if (!$storeMail) {
            $storeMail = $this->getConfigDataByFullPath(
                'trans_email/ident_general/email'
            );
        }

        $storesArr = array();
        foreach ($websites as $website) {
            $code = $website->getCode();
            $stores = $website->getStores();
            foreach ($stores as $store) {
                $storesArr[$store->getStoreId()] = $store->getData();
            }
        }

        if (count($storesArr) == 1) {
            try {
                $dataArr = array(
                    'stores' => array_pop($storesArr),
                    'version' => $version,
                );
            } catch (Exception $e) {
                $dataArr = array(
                    'stores' => array(),
                    'version' => $version,
                );
            }

            $dataArr['site'] = $url;
            $dataArr['email'] = $storeMail;

            $multistoreJson = json_encode($dataArr);
        } else {
            $multistoreData = $this->_createMultiStoreJson($storesArr);

            $multistoreDataByScope = $this->_createMultiStoreByScopeJson($storesArr);

            $dataArr = array(
                'stores' => $multistoreData,
                'stores2' => $multistoreDataByScope,
                'version' => $version,
            );

            $dataArr['site'] = $url;
            $dataArr['email'] = $storeMail;
            $multistoreJson = json_encode($dataArr);
        }

        return $multistoreJson;
    }

    /**
     * Get extension conflicts
     *
     * @param bool $all_conflicts comment
     *
     * @return array
     */
    public function getExtensionConflict($all_conflicts = false)
    {
        $all_rewrite_classes = array();
        $node_type_list = array('model', 'helper', 'block');

        foreach ($node_type_list as $node_type) {
            $children = Mage::getConfig()->getNode('modules')->children();

            foreach ($children as $name => $module) {
                if ($module->codePool == 'core' || $module->active != 'true') {
                    continue;
                }
                $config_file_path = Mage::getConfig()
                        ->getModuleDir('etc', $name).DS.'config.xml';
                $config = new Varien_Simplexml_Config();
                $config->loadString('<config/>');
                $config->loadFile($config_file_path);
                $config->extend($config, true);

                $nodes = $config->getNode()->global->{$node_type.'s'};
                if (!$nodes) {
                    continue;
                }
                foreach ($nodes->children() as $node_name => $config) {
                    if ($config->rewrite) {  // there is rewrite for current config
                        $reWriteChildren = $config->rewrite->children();

                        foreach ($reWriteChildren as $class_tag => $derived_class) {
                            $base_class_name = $this->_getMageBaseClass(
                                $node_type, $node_name, $class_tag
                            );

                            $lead_derived_class = '';
                            $conf = Mage::getConfig()
                                ->getNode()
                                ->global
                                ->{$node_type.'s'}->{$node_name};

                            if (isset($conf->rewrite->$class_tag)) {
                                $lead_derived_class = (string) $conf
                                    ->rewrite->$class_tag;
                            }
                            if ($derived_class == '') {
                                $derived_class = $lead_derived_class;
                            }

                            if (empty($all_rewrite_classes[$base_class_name])) {
                                $all_rewrite_classes[$base_class_name] = array(
                                    'derived' => array((string) $derived_class),
                                    'lead' => (string) $lead_derived_class,
                                    'tag' => $class_tag,
                                    'name' => array((string) $name),
                                );
                            } else {
                                array_push(
                                    $all_rewrite_classes[$base_class_name]['derived'],
                                    (string) $derived_class
                                );
                                array_push(
                                    $all_rewrite_classes[$base_class_name]['name'],
                                    (string) $name
                                );
                            }
                        }
                    }
                }
            }
        }
        if ($all_conflicts) {
            return $all_rewrite_classes;
        }

        $isp_rewrite_classes = array();
        $isp_module_name = 'Autocompleteplus_Autosuggest';
        foreach ($all_rewrite_classes as $base => $conflict_info) {
            /**
             * If isp extension rewrite this base class
             */
            if (in_array($isp_module_name, $conflict_info['name'])) {
                /**
                 * More then 1 class rewrite this base class => there is a conflict
                 */
                if (count($conflict_info['derived']) > 1) {
                    $isp_rewrite_classes[$base] = $conflict_info;
                }
            }
        }

        return $isp_rewrite_classes;
    }

    /**
     * Get Mage base class
     *
     * @param string $node_type param
     * @param string $node_name param
     * @param string $class_tag param
     *
     * @return string
     */
    protected function _getMageBaseClass($node_type, $node_name, $class_tag)
    {
        $config = Mage::getConfig()->getNode()->global->{$node_type.'s'}->$node_name;

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
     * Set Update Needed For Product
     *
     * @param mixed $read        param
     * @param mixed $write       param
     * @param mixed $product_id  param
     * @param mixed $product_sku param
     * @param mixed $store_id    param
     *
     * @return void
     */
    private function setUpdateNeededForProduct(
        $read,
        $write,
        $product_id,
        $product_sku,
        $store_id
    ) {
        if ($product_id == null) {
            return;
        }
        if ($product_sku == null) {
            $product_sku = 'dummy_sku';
        }
        try {
            $table_prefix = (string) Mage::getConfig()->getTablePrefix();
            $is_table_exist = $write
                ->showTableStatus($table_prefix.'autocompleteplus_batches');

            /**
             * Table not exists
             */
            if (!$is_table_exist) {
                return;
            }

            $sql_fetch = 'SELECT * FROM '.
                $table_prefix.
                'autocompleteplus_batches WHERE product_id=? AND store_id=?';
            $updates = $read->fetchAll($sql_fetch, array($product_id, $store_id));

            if ($updates && (count($updates) != 0)) {
                $sql = 'UPDATE '.$table_prefix.
                    'autocompleteplus_batches  SET update_date=?,action=? WHERE product_id=? AND store_id=?';
                $write->query(
                    $sql,
                    array(strtotime('now'), 'update', $product_id, $store_id)
                );
            } else {
                $sql = 'INSERT INTO '.
                    $table_prefix.
                    'autocompleteplus_batches (product_id,store_id,update_date,action,sku) VALUES (?,?,?,?,?)';
                $write->query(
                    $sql,
                    array($product_id,
                        $store_id,
                        strtotime('now'),
                        'update',
                        $product_sku
                    )
                );
            }
        } catch (Exception $e) {
            Mage::log(
                'Exception raised in setUpdateNeededForProduct()- '.$e->getMessage(),
                null,
                'autocompleteplus.log'
            );
            $this->ispErrorLog(
                'Exception raised in setUpdateNeededForProduct() - '.$e->getMessage()
            );
        }
    }

    /**
     * Delete product from table
     *
     * @param mixed $read         param
     * @param mixed $write        param
     * @param mixed $table_prefix param
     * @param int   $product_id   param
     * @param int   $store_id     param
     *
     * @return void
     */
    public function deleteProductFromTables($read, $write, $table_prefix, $product_id, $store_id)
    {
        $dt = strtotime('now');
        $sku = 'dummy_sku';
        $sqlFetch = 'SELECT * FROM '.$table_prefix.'autocompleteplus_batches WHERE product_id = ? AND store_id=?';
        $updates = $read->fetchAll($sqlFetch, array($product_id, $store_id));

        if ($updates && count($updates) != 0) {
            $sql = 'UPDATE '.$table_prefix.'autocompleteplus_batches SET update_date=?,action=? WHERE product_id = ? AND store_id = ?';
            $write->query($sql, array($dt, 'remove', $product_id, $store_id));
        } else {
            $sql = 'INSERT INTO '.$table_prefix.'autocompleteplus_batches  (product_id,store_id,update_date,action,sku) VALUES (?,?,?,?,?)';
            $write->query($sql, array($product_id, $store_id, $dt, 'remove', $sku));
        }

    }

    /**
     * IspLog
     *
     * @param string $log comment
     *
     * @return void
     */
    public function ispLog($log)
    {
        Mage::log($log, null, 'autocompleteplus.log');
    }

    /**
     * IspErrorLog
     *
     * @param string $log comment
     *
     * @return void
     */
    public function ispErrorLog($log)
    {
        $uuid = $this->getUUID();
        $site_url = $this->getConfigDataByFullPath('web/unsecure/base_url');
        $store_id = Mage::app()->getStore()->getStoreId();

        $server_url = $this->server_url.'/magento_logging_error';
        $request = $server_url.'?uuid='.$uuid.'&site_url='.
            $site_url.'&store_id='.$store_id.'&msg='.urlencode($log);

        $resp = $this->sendCurl($request);
    }

    /**
     * GetUUID
     *
     * @return mixed
     */
    public function getUUID()
    {
        return $this->getConfig()->getUUID();
    }

    /**
     * Deprecated, use getAuthorizationKey().
     * 
     * @return string | null
     */
    public function getKey()
    {
        return $this->getAuthorizationKey();
    }

    /**
     * GetAuthorizationKey
     *
     * @return string | null
     */
    public function getAuthorizationKey()
    {
        return $this->getConfig()->getAuthorizationKey();
    }

    /**
     * GetIsReachable
     *
     * @return string | null
     */
    public function getIsReachable()
    {
        return $this->getConfig()->isReachable();
    }

    /**
     * GetServerEndPoint
     *
     * @return string
     */
    public function getServerEndPoint()
    {
        try {
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            
            $write = Mage::getSingleton('core/resource')
                ->getConnection('core_write');
            
            $_tableprefix = (string) Mage::getConfig()->getTablePrefix();
            $tblExist = $write->showTableStatus(
                $_tableprefix.'autocompleteplus_config'
            );

            if (!$tblExist) {
                return '';
            }

            $sql = 'SELECT * FROM `'. 
                $_tableprefix.'autocompleteplus_config` WHERE `id` =1';
            $licenseData = $read->fetchAll($sql);
            if (array_key_exists('server_type', $licenseData[0])) {
                $key = $licenseData[0]['server_type'];
            } else {
                $key = '';
            }
        } catch (Exception $e) {
            $key = '';
        }

        return $key;
    }

    /**
     * SetServerEndPoint
     *
     * @param string $end_point comment
     *
     * @return void
     */
    public function setServerEndPoint($end_point)
    {
        try {
            $_tableprefix = (string) Mage::getConfig()->getTablePrefix();
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            
            $write = Mage::getSingleton('core/resource')
                ->getConnection('core_write');
            
            $tblExist = $write->showTableStatus(
                $_tableprefix.'autocompleteplus_config'
            );

            if (!$tblExist) {
                return;
            }

            $sqlFetch = 'SELECT * FROM '.$_tableprefix. 
                'autocompleteplus_config WHERE id = 1';
            $updates = $write->fetchAll($sqlFetch);

            if ($updates && count($updates) != 0) {
                $sql = 'UPDATE '.$_tableprefix. 
                    'autocompleteplus_config SET server_type=? WHERE id = 1';
                $write->query($sql, array($end_point));
            } else {
                Mage::log('cant update server_type', null, 'autocompleteplus.log');
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'autocompleteplus.log');
        }
    }

    /**
     * GetErrormessage
     *
     * @return string
     */
    public function getErrormessage()
    {
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string) Mage::getConfig()->getTablePrefix();

        $tblExist = $write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if (!$tblExist) {
            return '';
        }

        $sql = 'SELECT * FROM `'.
            $_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData = $read->fetchAll($sql);

        $errormessage = $licenseData[0]['errormessage'];

        return $errormessage;
    }

    /**
     * GetIfSyncWasInitiated
     *
     * @return bool
     */
    public function getIfSyncWasInitiated()
    {
        $collection = Mage::getModel('autocompleteplus_autosuggest/pusher')->getCollection();

        return $collection->getSize() > 0;
    }

    /**
     * GetPushId
     *
     * @return mixed
     */
    public function getPushId()
    {
        $collection = Mage::getModel('autocompleteplus_autosuggest/pusher')
            ->getCollection()
            ->addFilter('sent', 0);

        $collection->getSelect()->limit(1);
        $collection->load();

        return $collection->getLastItem()->getId();
    }

    /**
     * GetPushUrl
     *
     * @param null $id comment
     *
     * @return string
     */
    public function getPushUrl($id = null)
    {
        if ($id == null) {
            $id = $this->getPushId();
        }

        $url = Mage::getUrl();

        if (strpos($url, 'index.php') !== false) {
            if (substr($url, -1) != '/') {
                $url .= '/';
            }

            $url = $url.'autocompleteplus/products/pushbulk/pushid/'.$id;
        } else {
            $url = $url.'index.php/autocompleteplus/products/pushbulk/pushid/'.$id;
        }

        return $url;
    }

    public function escapeXml($xml)
    {
        /**
         *  $pairs = array(
         *      "\x03" => "&#x03;",
         *      "\x05" => "&#x05;",
         *      "\x0E" => "&#x0E;",
         *      "\x16" => "&#x16;",
         * );
         * $xml = strtr($xml, $pairs);
         */

        $xml = preg_replace('/[\x00-\x1f]/', '', $xml);

        return $xml;
    }

    /**
     * Get the session cookie value
     * protected with a salt (the store encryption key).
     *
     * @return string
     */
    public function getSessionId()
    {
        return md5(
            Mage::app()->getCookie()->get('frontend').$this->_getEncryptionKey()
        );
    }

    /**
     * Return encryption key in Magento to use as salt
     * Requires getting from configNode so that it is backward
     * compatible with later versions.
     *
     * @return string
     */
    protected function _getEncryptionKey()
    {
        return (string) Mage::getConfig()->getNode('global/crypt/key');
    }

    /**
     * CreateMultiStoreByScopeJson
     * 
     * @param array $storesArr comment
     *
     * @return array
     */
    protected function _createMultiStoreByScopeJson($storesArr)
    {
        $multistoreData = array();
        $storeComplete = array();

        $storeUrls = $this->getConfigMultiScopesDataByFullPath(
            'web/unsecure/base_url'
        );
        $locales = $this->getConfigMultiScopesDataByFullPath('general/locale/code');
        $useStoreCode = $this->getConfigDataByFullPath('web/url/use_store');

        foreach ($storesArr as $storeId => $value) {
            if (!$value['is_active']) {
                continue;
            }

            $storeComplete = $value;

            if (array_key_exists(self::STORES_SCOPE, $locales) 
                && array_key_exists($storeId, $locales[self::STORES_SCOPE])
            ) {
                $storeComplete['lang'] = $locales[self::STORES_SCOPE][$storeId];
            } elseif (array_key_exists(self::WEBSITES_SCOPE, $locales) 
                && array_key_exists(
                    $storeComplete[self::WEBSITE_ID], $locales[self::WEBSITES_SCOPE]
                )
            ) {
                $storeComplete['lang'] = $locales[self::WEBSITES_SCOPE]
                [$storeComplete[self::WEBSITE_ID]];
                
            } elseif (array_key_exists(self::DEFAULT_SCOPE, $locales) 
                && array_key_exists(0, $locales[self::DEFAULT_SCOPE])
            ) {
                $storeComplete['lang'] = $locales[self::DEFAULT_SCOPE][0];
            }

            if (!$useStoreCode) {
                if (array_key_exists(self::STORES_SCOPE, $storeUrls) 
                    && array_key_exists($storeId, $storeUrls[self::STORES_SCOPE])
                ) {
                    $storeComplete['url'] = $storeUrls[self::STORES_SCOPE][$storeId];
                } elseif (array_key_exists(self::WEBSITES_SCOPE, $storeUrls) 
                    && array_key_exists(
                        $storeComplete[self::WEBSITE_ID],
                        $storeUrls[self::WEBSITES_SCOPE]
                    )
                ) {
                    $storeComplete['url'] = $storeUrls[self::WEBSITES_SCOPE]
                    [$storeComplete[self::WEBSITE_ID]];
                    
                } elseif (array_key_exists(self::DEFAULT_SCOPE, $storeUrls) 
                    && array_key_exists(0, $storeUrls[self::DEFAULT_SCOPE])
                ) {
                    $storeComplete['url'] = $storeUrls[self::DEFAULT_SCOPE][0];
                }
            } else {
                $storeComplete['url'] = $storeUrls[0].$value['code'];
            }

            $multistoreData[] = $storeComplete;
        }

        return $multistoreData;
    }

    /**
     * CreateMultiStoreJson
     * 
     * @param array $storesArr comment
     *
     * @return array
     */
    protected function _createMultiStoreJson($storesArr)
    {
        $multistoreData = array();
        $storeComplete = array();

        $storeUrls = $this->getConfigMultiDataByFullPath('web/unsecure/base_url');
        $locales = $this->getConfigMultiDataByFullPath('general/locale/code');
        $useStoreCode = $this->getConfigDataByFullPath('web/url/use_store');

        foreach ($storesArr as $key => $value) {
            if (!$value['is_active']) {
                continue;
            }

            $storeComplete = $value;
            if (array_key_exists($key, $locales)) {
                $storeComplete['lang'] = $locales[$key];
            } else {
                $storeComplete['lang'] = $locales[0];
            }

            if (array_key_exists($key, $storeUrls)) {
                $storeComplete['url'] = $storeUrls[$key];
            } else {
                $storeComplete['url'] = $storeUrls[0];
            }

            if ($useStoreCode) {
                $storeComplete['url'] = $storeUrls[0].$value['code'];
            }

            $multistoreData[] = $storeComplete;
        }

        return $multistoreData;
    }
}
