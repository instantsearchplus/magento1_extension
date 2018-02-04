<?php

class Autocompleteplus_Autosuggest_Block_Inject extends Mage_Checkout_Block_Cart_Sidebar
{
    const AUTOCOMPLETE_JS_URL = 'https://acp-magento.appspot.com/js/acp-magento.js';

    public $_onCatalog = false;
    protected $_helper;

    protected function _construct()
    {
        $this->_helper = Mage::helper('autocompleteplus_autosuggest');
        $this->_uuid = $this->_helper->getUUID();

        //do not cache this block
        $this->setCacheLifetime(null);
    }

    /**
     * Test to see if admin is logged in 
     * by swapping session identifier
     * @return boolean 
     * @todo  rewrite this to be cleaner
     */
    protected function _isAdminLoggedIn()
    {
        try{
            //check if adminhtml cookie is set
            if(array_key_exists('adminhtml', $_COOKIE)){
                //get session path and add dir seperator and content field of cookie as data name with magento "sess_" prefix
                $sessionFilePath = Mage::getBaseDir('session').DS.'sess_'.$_COOKIE['adminhtml'];
                if (!file_exists($sessionFilePath)){
                    return false;
                }
                //write content of file in var
                $sessionFile = @file_get_contents($sessionFilePath);

                //save old session
                $oldSession = $_SESSION;
                //decode adminhtml session
                session_decode($sessionFile);
                //save session data from $_SESSION
                $adminSessionData = $_SESSION;
                //set old session back to current session
                $_SESSION = $oldSession;

                return array_key_exists('user', $adminSessionData['admin']);
            }
        } catch (Exception $e){}
    }

    /**
     * Get the current store code
     * @return string
     */
    public function getStoreId()
    {
        return Mage::app()->getStore()->getStoreId();
    }

    /**
     * Get the Magento version
     * @return string
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Get the AUTOCOMPLETEPLUS version
     * @return string
     * @todo  move to a helper
     */
    public function getVersion()
    {
        return Mage::helper('autocompleteplus_autosuggest')->getVersion();
    }

    /**
     * Get the current product
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * UUID getter
     * @return string
     */
    public function getUUID()
    {
        return $this->_uuid;
    }

    /**
     * Get the URL of the current product if it exists
     * @return string 
     */
    public function getProductUrl()
    {
        if($product = $this->getProduct()){
            return urlencode($product->getProductUrl());
        }
    }

    /**
     * Get the current product's SKU if the product exists
     * @return string
     */
    public function getProductSku()
    {
        if($product = $this->getProduct()){
            return $product->getSku();
        }
    }

    /**
     * Get the ID of the current product if it exists
     * @return string
     */
    public function getProductIdentifier()
    {
        if($product = $this->getProduct()){
            return $product->getId();
        }
    }

    public function getQuoteId()
    {
        return Mage::getSingleton('checkout/session')->getQuoteId();
    }

    /**
     * Return a formatted string for the <script src> attr
     * @return string
     */
    public function getSrc()
    {
        $parameters = array(
            'mage_v'        =>$this->getMagentoVersion(),
            'ext_v'         =>$this->getVersion(),
            'store'         =>$this->getStoreId(),
            'UUID'          =>$this->getUUID(),
            'product_url'   =>$this->getProductUrl(),
            'product_sku'   =>$this->getProductSku(),
            'product_id'    =>$this->getProductIdentifier(),
            'is_admin_user' =>$this->_isAdminLoggedIn(),
            'sessionID'     =>$this->_helper->getSessionId(),
            'QuoteID'       =>$this->getQuoteId()
        );

        return self::AUTOCOMPLETE_JS_URL . '?' . http_build_query($parameters,'','&');
    }
}