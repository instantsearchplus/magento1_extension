<?php
/**
 * Autocompleteplus_Autosuggest_Block_Inject
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
class Autocompleteplus_Autosuggest_Block_Inject extends Mage_Checkout_Block_Cart_Sidebar
{
    const AUTOCOMPLETE_JS_URL = 'https://acp-magento.appspot.com/js/acp-magento.js';

    public $_onCatalog = false;
    protected $_helper;

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_helper = Mage::helper('autocompleteplus_autosuggest');
        $config = Mage::getModel('autocompleteplus_autosuggest/config');
        $this->_uuid = $config->getUUID();

        //do not cache this block
        $this->setCacheLifetime(null);
    }

    /**
     * Test to see if admin is logged in
     * by swapping session identifier.
     *
     * @return bool
     */
    protected function _isAdminLoggedIn()
    {
        $io = new Varien_Io_File();
        $cookie = Mage::getModel('core/cookie');
        $sessionCookie = $cookie->get('adminhtml');
        $path = $io->getCleanPath(Mage::getBaseDir('session'));
        $sessionFilePath = $path.DS.'sess_'.$sessionCookie;

        /**
         * Check if adminhtml cookie is set
         */
        if ($sessionCookie) {
            /**
             * Get session path and add dir seperator
             * and content field of cookie as data name with magento "sess_" prefix
             */
            if (!$io->fileExists($sessionFilePath)) {
                return false;
            }
            //write content of file in var
            $io->open(array('path' => $path));
            $sessionFile = $io->read($sessionFilePath);
            if (stripos($sessionFile, 'Mage_Admin_Model_User') !== false) {
                return true;
            }
        }
    }

    /**
     * Get the current store code.
     *
     * @return string
     */
    public function getStoreId()
    {
        return Mage::app()->getStore()->getStoreId();
    }

    /**
     * Get the Magento version.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Get the AUTOCOMPLETEPLUS version.
     *
     * @return string
     */
    public function getVersion()
    {
        return Mage::helper('autocompleteplus_autosuggest')->getVersion();
    }

    /**
     * Get the current product.
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * UUID getter.
     *
     * @return string
     */
    public function getUUID()
    {
        return $this->_uuid;
    }

    /**
     * Get the URL of the current product if it exists.
     *
     * @return string
     */
    public function getProductUrl()
    {
        $product = $this->getProduct();

        if ($product != null) {
            return urlencode($product->getProductUrl());
        }
    }

    /**
     * Get the current product's SKU if the product exists.
     *
     * @return string
     */
    public function getProductSku()
    {
        $product = $this->getProduct();

        if ($product != null) {
            return $product->getSku();
        }
    }

    /**
     * Get the ID of the current product if it exists.
     *
     * @return string
     */
    public function getProductIdentifier()
    {
        $product = $this->getProduct();

        if ($product != null) {
            return $product->getId();
        }
    }

    /**
     * Returns quota Id
     *
     * @return mixed
     */
    public function getQuoteId()
    {
        return Mage::getSingleton('checkout/session')->getQuoteId();
    }

    /**
     * Checks if user is logged in as admin
     *
     * @return mixed
     */
    public function isLoggedInUser()
    {
        $session_customer = Mage::getSingleton('customer/session');

        return $session_customer->isLoggedIn();
    }

    /**
     * Return a formatted string for the <script src> attr.
     *
     * @return string
     */
    public function getSrc()
    {

        $parameters = array(
            'mage_v' => $this->getMagentoVersion(),
            'ext_v' => $this->getVersion(),
            'store' => $this->getStoreId(),
            'UUID' => $this->getUUID(),
            'product_url' => $this->getProductUrl(),
            'product_sku' => $this->getProductSku(),
            'product_id' => $this->getProductIdentifier(),
            'is_admin_user' => $this->_isAdminLoggedIn(),
            'sessionID' => $this->_helper->getSessionId(),
            'QuoteID' => $this->getQuoteId(),
            'is_user_logged_in' => $this->isLoggedInUser(),
        );

        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        /* If customer is logged in */
        if ($isLoggedIn) {
            $parameters['customer_group_id'] = Mage::getSingleton('customer/session')
                ->getCustomerGroupId();
        }

        return self::AUTOCOMPLETE_JS_URL.'?'.http_build_query($parameters, '', '&');
    }
}
