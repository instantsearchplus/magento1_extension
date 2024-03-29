<?php

class Autocompleteplus_Autosuggest_Model_Config extends Mage_Core_Model_Abstract
{
    protected $_helper = false;
    protected $_apiEndpoint = false;

    /**
     * Define XML Config paths and states.
     */
    const XML_SEARCH_LAYERED_DISABLED = 0;
    const XML_SEARCH_LAYERED_ENABLED = 1;
    const XML_SEARCH_LAYERED_CONFIG = 'autocompleteplus/config/layered';
    const XML_SMART_NAV_NATIVE_CONFIG = 'autocompleteplus/config/smart_nav_native';
    const XML_FORM_URL_CONFIG = 'autocompleteplus/config/miniform_change';
    const XML_API_ENDPOINT_CONFIG = 'default/autocompleteplus/config/api_endpoint';
    const XML_API_FULLTEXT_ENDPOINT_CONFIG = 'default/autocompleteplus/config/fulltext_endpoint';
    const XML_STORE_EMAIL_CONFIG = 'autocompleteplus/config/store_email';
    const XML_AUTHORIZATION_KEY_CONFIG = 'autocompleteplus_autosuggest/config/authorization_key';
    const XML_UUID_CONFIG = 'autocompleteplus_autosuggest/config/uuid';
    const XML_SITE_URL_CONFIG = 'autocompleteplus_autosuggest/config/site_url';
    const XML_IS_REACHABLE_CONFIG = 'autocompleteplus_autosuggest/config/is_reachable';
    const XML_ERROR_MESSAGE_CONFIG = 'autocompleteplus_autosuggest/config/error_message';
    const XML_EMAIL_RECOMMENDATIONS_CONFIG = 'autocompleteplus/config/email_recs';
    const XML_PATH_DROPDOWN_V2 = 'autocompleteplus/dropdown/v2';
    const XML_PATH_SERP_V2 = 'autocompleteplus/serp/v2';
    const XML_PATH_SMN_V2 = 'autocompleteplus/smn/v2';
    const XML_PATH_CUSTOM_VALUES = 'autocompleteplus/serp/custom_values';

    /**
     * Fetch Magento Config Model.
     *
     * @return false|Mage_Core_Model_Config
     */
    protected function _getMageConfig()
    {
        return Mage::getModel('core/config');
    }

    /**
     * Fetch API Endpoint URL.
     *
     * @return string|Mage_Core_Model_Config_Element
     */
    public function getEndpoint()
    {
        if (!$this->_apiEndpoint) {
            $this->_apiEndpoint = Mage::getConfig()->getNode(self::XML_API_ENDPOINT_CONFIG);
        }

        return $this->_apiEndpoint;
    }

    /**
     * Fetch AA Helper.
     *
     * @return bool|Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('autocompleteplus_autosuggest');
        }

        return $this->_helper;
    }

    /**
     * Enable Layered Navigation.
     *
     * @param string $scope
     * @param int    $scopeId
     */
    public function enableLayeredNavigation($scope = 'stores', $scopeId = 0)
    {
        $this->_getMageConfig()->deleteConfig(self::XML_SEARCH_LAYERED_CONFIG, 'default', $scopeId);
        $this->_getMageConfig()->saveConfig(self::XML_SEARCH_LAYERED_CONFIG, self::XML_SEARCH_LAYERED_ENABLED, $scope, $scopeId);
    }

    /**
     * Disable Layered Navigation.
     *
     * @param string $scope
     * @param int    $scopeId
     */
    public function disableLayeredNavigation($scope = 'stores', $scopeId = 0)
    {
        $this->_getMageConfig()->deleteConfig(self::XML_SEARCH_LAYERED_CONFIG, 'default', $scopeId);
        $this->_getMageConfig()->saveConfig(self::XML_SEARCH_LAYERED_CONFIG, self::XML_SEARCH_LAYERED_DISABLED, $scope, $scopeId);
    }

    /**
     * Get Layered Navigation Status.
     *
     * @param $scopeId
     *
     * @return mixed
     */
    public function getLayeredNavigationStatus($scopeId)
    {
        return Mage::getStoreConfig(self::XML_SEARCH_LAYERED_CONFIG, $scopeId);
    }

    /**
     * Enable Layered Navigation.
     *
     * @param string $scope
     * @param int    $scopeId
     */
    public function enableMiniFormUrlRewrite($scope = 'stores', $scopeId = 0)
    {
        $this->_getMageConfig()->deleteConfig(self::XML_FORM_URL_CONFIG, 'default', $scopeId);
        $this->_getMageConfig()->saveConfig(self::XML_FORM_URL_CONFIG, self::XML_SEARCH_LAYERED_ENABLED, $scope, $scopeId);
    }

    /**
     * Disable Layered Navigation.
     *
     * @param string $scope
     * @param int    $scopeId
     */
    public function disableMiniFormUrlRewrite($scope = 'stores', $scopeId = 0)
    {
        $this->_getMageConfig()->deleteConfig(self::XML_FORM_URL_CONFIG, 'default', $scopeId);
        $this->_getMageConfig()->saveConfig(self::XML_FORM_URL_CONFIG, self::XML_SEARCH_LAYERED_DISABLED, $scope, $scopeId);
    }

    /**
     * Switch Smart native navigation.
     *
     * @param string $scope
     * @param int    $scopeId
     */
    public function switchSmartNavigationNative($state, $scope = 'stores', $scopeId = 0)
    {
        if ($state == 'on') {
            $this->_getMageConfig()->deleteConfig(self::XML_SMART_NAV_NATIVE_CONFIG, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_SMART_NAV_NATIVE_CONFIG, self::XML_SEARCH_LAYERED_ENABLED, $scope, $scopeId);
        } else {
            $this->_getMageConfig()->deleteConfig(self::XML_SMART_NAV_NATIVE_CONFIG, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_SMART_NAV_NATIVE_CONFIG, self::XML_SEARCH_LAYERED_DISABLED, $scope, $scopeId);
        }

    }

    public function setDropdownV2($val, $scopeId = 0, $scope = 'stores')
    {
        if ($val == 'true' || $val == '1') {
            $this->_getMageConfig()->deleteConfig(self::XML_PATH_DROPDOWN_V2, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_PATH_DROPDOWN_V2, self::XML_SEARCH_LAYERED_ENABLED, $scope, $scopeId);
        } else {
            $this->_getMageConfig()->deleteConfig(self::XML_PATH_DROPDOWN_V2, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_PATH_DROPDOWN_V2, self::XML_SEARCH_LAYERED_DISABLED, $scope, $scopeId);
        }
    }

    public function setSerpV2($val, $scopeId = 0, $scope = 'stores')
    {
        if ($val == 'true' || $val == '1') {
            $this->_getMageConfig()->deleteConfig(self::XML_PATH_SERP_V2, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_PATH_SERP_V2, self::XML_SEARCH_LAYERED_ENABLED, $scope, $scopeId);
        } else {
            $this->_getMageConfig()->deleteConfig(self::XML_PATH_SERP_V2, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_PATH_SERP_V2, self::XML_SEARCH_LAYERED_DISABLED, $scope, $scopeId);
        }
    }

    public function setSmnV2($val, $scopeId = 0, $scope = 'stores')
    {
        if ($val == 'true' || $val == '1') {
            $this->_getMageConfig()->deleteConfig(self::XML_PATH_SMN_V2, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_PATH_SMN_V2, self::XML_SEARCH_LAYERED_ENABLED, $scope, $scopeId);
        } else {
            $this->_getMageConfig()->deleteConfig(self::XML_PATH_SMN_V2, 'default', $scopeId);
            $this->_getMageConfig()->saveConfig(self::XML_PATH_SMN_V2, self::XML_SEARCH_LAYERED_DISABLED, $scope, $scopeId);
        }
    }

    public function setCustomValues($val, $scopeId = 0, $scope = 'stores')
    {
        $this->_getMageConfig()->deleteConfig(self::XML_PATH_CUSTOM_VALUES, 'default', $scopeId);
        $this->_getMageConfig()->saveConfig(self::XML_PATH_CUSTOM_VALUES, $val, $scope, $scopeId);
    }

    /**
     * Set Authorization Key.
     *
     * @param $key
     */
    public function setAuthorizationKey($key)
    {
        $this->_getMageConfig()->saveConfig(self::XML_AUTHORIZATION_KEY_CONFIG, $key);
    }

    /**
     * Get Authorization Key.
     *
     * @return mixed
     */
    public function getAuthorizationKey()
    {
        return Mage::getStoreConfig(self::XML_AUTHORIZATION_KEY_CONFIG);
    }

    /**
     * Set Server URL.
     *
     * @param $key
     */
    public function setFulltextSearchEndpoint($endpoint)
    {
        $this->_getMageConfig()->saveConfig(self::XML_API_FULLTEXT_ENDPOINT_CONFIG, $endpoint);
    }

    /**
     * Get Server URL.
     *
     * @return mixed
     */
    public function getFulltextSearchEndpoint()
    {
        return Mage::getStoreConfig(self::XML_API_FULLTEXT_ENDPOINT_CONFIG);
    }

    /**
     * Set UUID.
     *
     * @param $uuid
     */
    public function setUUID($uuid)
    {
        $this->_getMageConfig()->saveConfig(self::XML_UUID_CONFIG, $uuid);
    }

    /**
     * Get UUID.
     *
     * @return mixed
     */
    public function getUUID()
    {
        return Mage::getStoreConfig(self::XML_UUID_CONFIG);
    }

    /**
     * Get DropdownV2.
     *
     * @return mixed
     */
    public function getDropdownV2($scopeId)
    {
        return Mage::getStoreConfig(self::XML_PATH_DROPDOWN_V2, $scopeId);
    }

    /**
     * Get SerpV2.
     *
     * @return mixed
     */
    public function getSerpV2($scopeId)
    {
        return Mage::getStoreConfig(self::XML_PATH_SERP_V2, $scopeId);
    }

    /**
     * Get SmnV2.
     *
     * @return mixed
     */
    public function getSmnV2($scopeId)
    {
        return Mage::getStoreConfig(self::XML_PATH_SMN_V2, $scopeId);
    }

    /**
     * Get CustomValues.
     *
     * @return mixed
     */
    public function getCustomValues($scopeId)
    {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOM_VALUES, $scopeId);
    }


    /**
     * Get UUID and Authorization key.
     *
     * @return array
     */
    public function getBothKeys()
    {
        return array('uuid' => $this->getUUID(), 'authkey' => $this->getAuthorizationKey());
    }

    /**
     * Set Site URL.
     *
     * @param $url
     */
    public function setSiteUrl($url)
    {
        $this->_getMageConfig()->saveConfig(self::XML_SITE_URL_CONFIG, $url);
    }

    /**
     * Get Site URL.
     *
     * @return mixed
     */
    public function getSiteUrl()
    {
        return Mage::getStoreConfig(self::XML_SITE_URL_CONFIG);
    }

    /**
     * Set Is Reachable.
     *
     * @param $reachable
     */
    public function setIsReachable($reachable)
    {
        $this->_getMageConfig()->saveConfig(self::XML_IS_REACHABLE_CONFIG, $reachable);
    }

    /**
     * Get Is Reachable.
     *
     * @return mixed
     */
    public function isReachable()
    {
        return Mage::getStoreConfig(self::XML_IS_REACHABLE_CONFIG);
    }

    /**
     * Set Error Message.
     *
     * @param $message
     */
    public function setErrorMessage($message)
    {
        $this->_getMageConfig()->saveConfig(self::XML_ERROR_MESSAGE_CONFIG, $message);
    }

    /**
     * Get Error Message.
     *
     * @return mixed
     */
    public function getErrorMessage()
    {
        return Mage::getStoreConfig(self::XML_ERROR_MESSAGE_CONFIG);
    }

    /**
     * Get Module Version.
     *
     * @return mixed
     */
    public function getModuleVersion()
    {
        return Mage::getConfig()->getModuleConfig('Autocompleteplus_Autosuggest')->version;
    }

    /**
     * Update robots.txt file with ISP sitemap URL.
     *
     * @param $responseData
     */
    protected function _updateRobotsTxt($responseData)
    {
        $fileIo = new Varien_Io_File();
        $baseDir = Mage::getBaseDir();
        $fileIo->open(array('path' => $baseDir));
        $robotsTxtContent = $fileIo->read('robots.txt');
        $siteMapUrl = 'Sitemap:http://sync.fastsimon.com/ext_sitemap?u='.$responseData['uuid'].PHP_EOL;
        $robotsTxtExists = $fileIo->fileExists('robots.txt');
        $baseDirWritable = $fileIo->isWriteable($baseDir);
        $siteMapExists = strpos($robotsTxtContent, $siteMapUrl) === false;
        $robotsTxtWritable = $fileIo->isWriteable('robots.txt');

        if ($robotsTxtExists && $robotsTxtWritable && !$siteMapExists) {
            $fileIo->write('robots.txt', $robotsTxtContent.$siteMapUrl);
        } elseif (!$robotsTxtExists && $baseDirWritable) {
            $fileIo->write('robots.txt', $siteMapUrl);
        } else {
            $this->_sendError('Unable to properly update robots.txt with ISP Sitemap');
        }
    }

    /**
     * Generate Config for AutocompletePlus.
     *
     * @param string $UUID
     * @param string $key
     *
     * @return $this
     *
     * @throws Zend_Http_Client_Exception
     */
    public function generateConfig($UUID = null, $key = null)
    {
        $params = array(
            'site' => $this->_getHelper()->getConfigDataByFullPath('web/unsecure/base_url'),
            'email' => Mage::getStoreConfig(self::XML_STORE_EMAIL_CONFIG),
            'f' => $this->_getHelper()->getVersion(),
            'multistore' => $this->_getHelper()->getMultiStoreDataJson(),
        );

        if ($UUID && $key) {
            $params['uuid'] = $UUID;
            $params['key'] = $key;
        }

        $responseData = null;

        // @codingStandardsIgnoreStart
        /**
         * Due to backward compatibility issues with Magento < 1.8.1 and cURL/Zend
         * We need to use PHP's implementation of cURL directly rather than Zend or Varien.
         */
        $client = curl_init($this->getEndpoint().'/install');
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_POSTFIELDS, $params);

        $response = curl_exec($client);
        curl_close($client);
        // @codingStandardsIgnoreEnd

        if (!$response && ($key == null || $key == '')) {
            $notSecureUrl = str_replace('https', 'http', $this->getEndpoint().'/install');

            $client = curl_init($notSecureUrl);
            curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($client, CURLOPT_POSTFIELDS, $params);
            curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);

            $response = curl_exec($client);
            curl_close($client);
        }

        if ($response) {
            $responseData = json_decode($response, true);

            /*
             * Validate uuid exists
             */
            if (isset($responseData['uuid']) && strlen($responseData['uuid']) > 50) {
                Mage::log('Registration failed - please check response below', null, 'autocomplete.log', true);
                $this->_sendError('Could not get license string.');

                return false;
            } elseif (!isset($responseData['uuid'])) {
                Mage::log('Registration failed - please check response below', null, 'autocomplete.log', true);
                $this->_sendError('Could not get license string.');

                return false;
            }

            $this->_updateRobotsTxt($responseData);
        }

        if (isset($responseData['authentication_key'])) {
            $this->setAuthorizationKey($responseData['authentication_key']);
            $this->setUUID($responseData['uuid']);
            $this->setSiteUrl($this->_getHelper()->getConfigDataByFullPath('web/unsecure/base_url'));
            $this->setIsReachable($responseData['is_reachable']);
            $this->setErrorMessage(isset($errorMessage) ? $errorMessage : '');

            if (!$this->isConfigDataValid($responseData['uuid'], $responseData['authentication_key'])) {
                $this->_sendError('UUID or Authentication key are not valid | got UUID: '.$responseData['uuid'].
                    ' | authentication_key: '.$responseData['authentication_key']);
            }
        }

        Mage::dispatchEvent('autocompleteplus_autosuggest_config_creation_after',
            array('config' => $this, 'response' => $response, 'responseData' => $responseData));

        return $this;
    }

    /**
     * Send error to API.
     *
     * @param string $message
     *
     * @return Zend_Http_Response
     *
     * @throws Zend_Http_Client_Exception
     */
    protected function _sendError($message = 'No Message Provided')
    {
        $params = array(
            'site' => $this->_getHelper()->getConfigDataByFullPath('web/unsecure/base_url'),
            'msg' => $message,
            'email' => Mage::getStoreConfig(self::XML_STORE_EMAIL_CONFIG),
            'multistore' => $this->_getHelper()->getMultiStoreDataJson(),
            'f' => $this->getModuleVersion(),
        );

        // @codingStandardsIgnoreStart
        /**
         * Due to backward compatibility issues with Magento < 1.8.1 and cURL/Zend
         * We need to use PHP's implementation of cURL directly rather than Zend or Varien.
         */
        $client = curl_init($this->getEndpoint().'/install_error');
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($client);
        curl_close($client);
        // @codingStandardsIgnoreEnd
        return $response;
    }

    /**
     * Support deprecated functionality - needed? Or can we change the install scripts?
     */
    public function getCollection()
    {
        return new Varien_Object(array(
            'authkey' => $this->getAuthorizationKey(),
            'licensekey' => $this->getUUID(),
        ));
    }

    public function isConfigDataValid($input_uuid = null, $input_key = null)
    {
        $uuid = ($input_uuid) ? $input_uuid : $this->getUUID();
        $authentication_key = ($input_key) ? $input_key : $this->getAuthorizationKey();

        if (!$uuid || strlen($uuid) != 36 || substr_count($uuid, '-') != 4) {
            return false;
        }
        if (!$authentication_key || strlen($authentication_key) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Set Email Recommendations Status
     *
     * @param string $scope
     * @param int    $scopeId
     * @param int  $val
     */
    public function setEmailRecsStatus($scope = 'stores', $scopeId = 0, $val)
    {
        $this->_getMageConfig()->saveConfig(self::XML_EMAIL_RECOMMENDATIONS_CONFIG, $val, $scope, $scopeId);
    }

    /**
     * Get Email Recommendations Status.
     *
     * @param $scopeId
     *
     * @return mixed
     */
    public function getEmailRecsStatus($scopeId)
    {
        return Mage::getStoreConfig(self::XML_EMAIL_RECOMMENDATIONS_CONFIG, $scopeId);
    }
}
