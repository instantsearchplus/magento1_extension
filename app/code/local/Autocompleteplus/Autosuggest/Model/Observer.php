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
class Autocompleteplus_Autosuggest_Model_Observer extends Mage_Core_Model_Abstract
{
    const AUTOCOMPLETEPLUS_WEBHOOK_URI = 'https://acp-magento.appspot.com/ma_webhook';
    const API_UPDATE_URI = 'http://magento.autocompleteplus.com/update';
    const WEBHOOK_CURL_TIMEOUT_LENGTH = 2;

    protected $imageField;
    protected $standardImageFields = array();
    protected $currency;
    protected $batchesHelper;

    public function _construct()
    {
        $this->imageField = Mage::getStoreConfig('autocompleteplus/config/imagefield');
        if (!$this->imageField) {
            $this->imageField = 'thumbnail';
        }

        $this->standardImageFields = array('image', 'small_image', 'thumbnail');
        $this->currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $this->batchesHelper = Mage::helper('autocompleteplus_autosuggest/batches');
    }

    public function getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
    }

    protected function _generateProductXml($product)
    {
        $catalog = new SimpleXMLElement('<catalog></catalog>');
        try {
            if (in_array($this->imageField, $this->standardImageFields)) {
                $productImage = Mage::helper('catalog/image')->init($product, $this->imageField);
            } else {
                $function = 'get'.$this->imageField;
                $productImage = $product->$function();
            }
        } catch (Exception $e) {
            $productImage = '';
        }
        $productUrl = Mage::helper('catalog/product')->getProductUrl($product->getId());
        $status = $product->isInStock();
        $stockItem = $product->getStockItem();
        if ($stockItem && $stockItem->getIsInStock() && $status) {
            $saleable = 1;
        } else {
            $saleable = 0;
        }

        // Add Magento Module Version attribute
        $catalog->addAttribute('version', $this->getConfig()->getModuleVersion());

        // Add Magento Version attribute
        $catalog->addAttribute('magento', Mage::getVersion());

        // Create product child
        $productChild = $catalog->addChild('product');

        $productChild->addAttribute('store', $product->getStoreId());
        $productChild->addAttribute('currency', $this->currency);
        $productChild->addAttribute('visibility', $product->getVisibility());
        $productChild->addAttribute('price', $this->_getPrice($product));
        $productChild->addAttribute('url', $productUrl);
        $productChild->addAttribute('thumbs', $productImage);
        $productChild->addAttribute('selleable', $saleable);
        $productChild->addAttribute('action', 'update');

        $productChild->addChild('description', '<![CDATA['.$product->getDescription().']]>');
        $productChild->addChild('short', '<![CDATA['.$product->getShortDescription().']]>');
        $productChild->addChild('name', '<![CDATA['.$product->getName().']]>');
        $productChild->addChild('sku', '<![CDATA['.$product->getSku().']]>');

        return $catalog->asXML();
    }

    public function catalog_product_save_after_depr($observer)
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $product = $observer->getProduct();
        $this->imageField = Mage::getStoreConfig('autocompleteplus/config/imagefield');
        if (!$this->imageField) {
            $this->imageField = 'thumbnail';
        }
        $this->standardImageFields = array('image', 'small_image', 'thumbnail');
        $this->currency = Mage::app()->getStore()->getCurrentCurrencyCode();
        $domain = Mage::getStoreConfig('web/unsecure/base_url');
        $key = $this->getConfig()->getUUID();

        $xml = $this->_generateProductXml($product);
        $data = array(
            'site' => $domain,
            'key' => $key,
            'catalog' => $xml,
        );
        $res = $this->_sendUpdate($data);
        Mage::log($res, null, 'autocomplete.log');
    }

    protected function _getPrice($product)
    {
        $price = 0;
        $helper = Mage::helper('autocompleteplus_autosuggest');
        if ($product->getTypeId() == 'grouped') {
            $helper->prepareGroupedProductPrice($product);
            $_minimalPriceValue = $product->getPrice();
            if ($_minimalPriceValue) {
                $price = $_minimalPriceValue;
            }
        } elseif ($product->getTypeId() == 'bundle') {
            if (!$product->getFinalPrice()) {
                $price = $helper->getBundlePrice($product);
            } else {
                $price = $product->getFinalPrice();
            }
        } else {
            $price = $product->getFinalPrice();
        }
        if (!$price) {
            $price = 0;
        }

        return $price;
    }

    protected function _sendUpdate($data)
    {
        // @codingStandardsIgnoreStart
        $client = curl_init(self::API_UPDATE_URI);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($client);
        curl_close($client);
        // @codingStandardsIgnoreEnd
        return $response;
    }

    public function catalog_product_save_light($observer) {
        $productId = $observer->getProductId();
        $dt = Mage::getSingleton('core/date')->gmtTimestamp();
        $parent_ids = $this->batchesHelper->get_parent_products_ids($productId);
        $this->batchesHelper->writeProductUpdate(
            $productId,
            $dt,
            null,
            $parent_ids
        );
    }

    /**
     * Method catalog_product_save_after executes BEFORE
     * product save
     *
     * @param $observer
     */
    public function catalog_product_save_after($observer)
    {
        $product = $observer->getProduct();
        $origData = $observer->getProduct()->getOrigData();
        $productId = $product->getId();
        $product_stores = $product->getStoreIds();
        $sku = $product->getSku();

        if (is_array($origData) &&
            array_key_exists('sku', $origData)) {
            $oldSku = $origData['sku'];
            if ($sku != $oldSku) {
                $this->batchesHelper
                    ->writeProductDeletion($oldSku, $productId, null, $product_stores);
            }
        }

        //recording disabled item as deleted
        if ($product->getStatus() == '2') {
            $this->batchesHelper
                ->writeProductDeletion($sku, $productId, null, $product_stores);
            return;
        }

        $dt = Mage::getSingleton('core/date')->gmtTimestamp();

        $simple_product_parents = $this->batchesHelper->get_parent_products_ids($product);

        $this->batchesHelper->writeProductUpdate(
            $productId,
            $dt,
            $sku,
            $simple_product_parents,
            $product_stores
        );
    }

    /**
     * Method executes AFTER product save
     *
     * @param $observer
     */
    public function catalog_product_save_after_real($observer)
    {
        $product = $observer->getProduct();

        $productId = $product->getId();

        $sku = $product->getSku();

        try {
            $updates = Mage::getModel('autocompleteplus_autosuggest/batches')->getCollection()
                ->addFieldToFilter('product_id', array('null' => true))
                ->addFieldToFilter('sku', $sku)
            ;

            foreach ($updates as $update) {
                $update->setProductId($productId);

                $update->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function catalog_product_import_finish_before($observer){
        try {
            if (
                !method_exists($observer->getAdapter(), 'getBehavior')
                || Mage_ImportExport_Model_Import::BEHAVIOR_DELETE == $observer->getAdapter()->getBehavior()
            ) {
                return; //we do not support delete from csv
            }
            if ($observer->getAdapter()->getEntityTypeID() != '4') {
                return;
            }

            $importedData = $observer->getAdapter()->getNewSku();

            $productIds = array();
            foreach ($importedData as $sku=>$item) {
                $productIds[] = intval($item['entity_id']);
            }
            $productCollection = Mage::getModel('catalog/product')
                ->getCollection();
            $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
            $counter = 0;
            foreach ($productCollection as $product) {
                $simple_product_parents = $this->batchesHelper->get_parent_products_ids($product);

                $this->batchesHelper->writeProductUpdate(
                    $product->getID(),
                    ((int)Mage::getSingleton('core/date')->gmtTimestamp() + $counter),
                    $product->getSku(),
                    $simple_product_parents
                );
                $counter++;
            }

        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'autocomplete.log');
        }
    }

    public function catalog_product_delete_before($observer)
    {
        $product = $observer->getProduct();
        $productId = $product->getId();
        $simple_product_parents = $this->batchesHelper->get_parent_products_ids($product);
        $sku = $product->getSku();
        $this->batchesHelper
            ->writeProductDeletion($sku, $productId, $simple_product_parents);
    }

    public function catalog_controller_product_mass_status($observer)
    {
        $productsIds = $observer->getEvent()->getProductIds();
        if ($productsIds == null) {
            $productsIds = $observer->getEvent()->getProducts();
        }
        $status = Mage::app()->getRequest()->getParam('status');
        $attributes = Mage::app()->getRequest()->getParam('attributes');
        $dt = Mage::getSingleton('core/date')->gmtTimestamp();
        foreach ($productsIds as $prod_id) {
            $parent_ids = $this->batchesHelper->get_parent_products_ids($prod_id);
            if (($status && $status == '2')
                || ($attributes && array_key_exists('status', $attributes)
                    && $attributes['status'] == '2')) {
                $this->batchesHelper
                    ->writeProductDeletion('dummy_sku', $prod_id, $parent_ids, null);
            } else {
                $this->batchesHelper->writeProductUpdate(
                    $prod_id,
                    $dt,
                    'dummy_sku',
                    $parent_ids
                );
            }

        }
    }

    public function adminSessionUserLoginSuccess()
    {
        $notifications = array();
        /** @var Autocompleteplus_Autosuggest_Helper_Data $helper */
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $command = 'http://magento.autocompleteplus.com/ext_info?u='.$this->getConfig()->getUUID();
        $res = $helper->sendCurl($command);
        $result = json_decode($res);
        if (isset($result->alerts)) {
            foreach ($result->alerts as $alert) {
                $notification = array(
                    'type' => (string) $alert->type,
                    'message' => (string) $alert->message,
                    'timestamp' => (string) $alert->timestamp,
                );
                if (isset($alert->subject)) {
                    $notification['subject'] = (string) $alert->subject;
                }
                $notifications[] = $notification;
            }
        }
        if (!empty($notifications)) {
            Mage::getResourceModel('autocompleteplus_autosuggest/notifications')->addNotifications($notifications);
        }
        $this->sendNotificationMails();
    }

    public function sendNotificationMails()
    {
        /** @var Autocompleteplus_Autosuggest_Model_Mysql4_Notifications_Collection $notifications */
        $notifications = Mage::getModel('autocompleteplus_autosuggest/notifications')->getCollection();
        $notifications->addTypeFilter('email')->addActiveFilter();
        foreach ($notifications as $notification) {
            $this->_sendStatusMail($notification);
        }
    }

    /**
     * @param Autocompleteplus_Autosuggest_Model_Notifications $notification
     */
    protected function _sendStatusMail($notification)
    {
        /** @var Autocompleteplus_Autosuggest_Helper_Data $helper */
        $helper = Mage::helper('autocompleteplus_autosuggest');
        // Getting site owner email
        $storeMail = $helper->getConfigDataByFullPath('autocompleteplus/config/store_email');
        if ($storeMail) {
            $emailTemplate = Mage::getModel('core/email_template');
            $emailTemplate->loadDefault('autosuggest_status_notification');
            $emailTemplate->setTemplateSubject($notification->getSubject());
            // Get General email address (Admin->Configuration->General->Store Email Addresses)
            $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/email'));
            $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/name'));
            $emailTemplateVariables['message'] = $notification->getMessage();
            $emailTemplate->send($storeMail, null, $emailTemplateVariables);
            $notification->setIsActive(0)
                ->save();
        }
    }

    /**
     * The generic webhook service caller.
     *
     * @param Varien_Event_Observer $observer
     */
    public function webhook_service_call($observer)
    {
        try {
            $eventName = $observer->getEvent()->getName();
            if ($eventName == 'controller_isp_custom_onepage_success') {
                $eventName = 'controller_action_postdispatch_checkout_onepage_success';
            } else {
                Mage::getSingleton('core/session')->setIspQuoteID($this->getQuoteId());
            }
            $hook_url = $this->_getWebhookObjectUri($eventName, $observer);
            if(function_exists('fsockopen')) {
                $this->post_without_wait(
                    $hook_url,
                    array(),
                    'GET'
                );
            } else {
                /**
                 * Due to backward compatibility issues with Magento < 1.8.1 and cURL/Zend
                 * We need to use PHP's implementation of cURL directly rather than Zend or Varien.
                 */
                $client = curl_init($hook_url);
                curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($client);
                $res_obj = json_decode($response);
                //Mage::log(print_r($res_obj, true), null, 'autocomplete.log', true);
                curl_close($client);
            }

        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'autocomplete.log', true);
        }
    }

    /**
     * post_without_wait send http call and close the connection without waiting for response
     *
     * @param $url
     * @param array $params
     * @param string $type
     *
     * @return void
     */
    private function post_without_wait($url, $params=array(), $type='POST', $post_params=array())
    {
        foreach ($params as $key => &$val) {
            if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key.'='.urlencode($val);
        }

        $post_string = implode('&', $post_params);
        $parts=parse_url($url);

        if ($type == 'GET') {
            $post_string = $parts['query'];
        }

        $fp = fsockopen($parts['host'],
            isset($parts['port'])?$parts['port']:80,
            $errno, $errstr, 30);

        // Data goes in the path for a GET request
        if('GET' == $type) {
            $parts['path'] .= '?'.$post_string;
        }

        $out = "$type ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";

        if ($type == 'POST') {
            $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out.= "Content-Length: ".strlen($post_string)."\r\n";
        }

        $out.= "Connection: Close\r\n\r\n";
        // Data goes in the request body for a POST request
        if ('POST' == $type && isset($post_string)) {
            $out.= $post_string;
        }

        fwrite($fp, $out);
        fclose($fp);
    }

    /**
     * Create the webhook URI.
     *
     * @return string
     */
    protected function _getWebhookObjectUri($event_name, $observer)
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $cart_items = $this->_getVisibleItems($observer);
        $cart_products_json = json_encode($cart_items);
        $store_id = Mage::app()->getStore()->getStoreId();
        if ($event_name == 'controller_action_postdispatch_checkout_onepage_success'
            && Mage::getStoreConfig('cataloginventory/options/show_out_of_stock') == '0') {
            $dt = Mage::getSingleton('core/date')->gmtTimestamp();
            foreach ($cart_items as $prod) {
                $stockData = Mage::getModel('cataloginventory/stock_item')
                    ->loadByProduct($prod['product_id']);
                $isInStock = $stockData->getIsInStock();
                if ($stockData->getTypeId() == 'simple') {
                    if ($isInStock == '0') {
                        $this->batchesHelper
                            ->writeProductUpdate(
                                intval($prod['product_id']),
                                $dt,
                                null,
                                $this->batchesHelper->get_parent_products_ids(intval($prod['product_id']))
                            );
                    }
                } else {
                    /*
                     * sending product to updates queue,
                     * since we do not know if it became out of stock
                    */
                    $product = Mage::getModel('catalog/product')->load($prod['product_id']);
                    $this->batchesHelper->writeProductUpdate(
                        intval($prod['product_id']),
                        $dt,
                        $product->getSku(),
                        $this->batchesHelper->get_parent_products_ids(intval($prod['product_id']))
                    );
                }
            }
        }

        $parameters = array(
            'event' => $this->getWebhookEventLabel($event_name),
            'UUID' => $this->getConfig()->getUUID(),
            'key' => $this->getConfig()->getAuthorizationKey(),
            'store_id' => $store_id,
            'st' => $helper->getSessionId(),
            'cart_token' => $this->getQuoteId(),
            'serp' => '',
            'cart_product' => $cart_products_json,
        );

        return static::AUTOCOMPLETEPLUS_WEBHOOK_URI.'?'.http_build_query($parameters, '', '&');
    }

    /**
     * Return a label for webhooks based on the current
     * controller route. This cannot be handled by layout
     * XML because the layout engine may not be init in all
     * future uses of the webhook.
     *
     * @return string|void
     */
    public function getWebhookEventLabel($event_name)
    {
        switch ($event_name) {
            case 'controller_action_postdispatch_checkout_cart_index':
                return 'cart';
            case 'controller_action_postdispatch_checkout_onepage_index':
                return 'checkout';
            case 'controller_action_postdispatch_checkout_onepage_success':
                return 'success';
            default:
                return null;
        }
    }

    /**
     * Returns the quote id if it exists, otherwise it will
     * return the last order id. This only is set in the session
     * when an order has been recently completed. Therefore
     * this call may also return null.
     *
     * @return string|null
     */
    public function getQuoteId()
    {
        if ($quoteId = Mage::getSingleton('checkout/session')->getQuoteId()) {
            return $quoteId;
        }

        $quoteId = $this->getOrder()->getQuoteId();
        if (!$quoteId) {
            $quoteId = Mage::getSingleton('core/session')->getIspQuoteID();
        }
        return $quoteId;
    }

    /**
     * Get the order associated with the previous quote id
     * used as a fallback when the quote is no longer available.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();

        return Mage::getModel('sales/order')->load($orderId);
    }

    /**
     * JSON encode the cart contents.
     *
     * @return string
     */
    public function getCartContentsAsJson()
    {
        return json_encode($this->_getVisibleItems());
    }

    /**
     * Format visible cart contents into a multidimensional keyed array.
     *
     * @return array
     */
    protected function _getVisibleItems($observer=null)
    {
        if ($observer && $observer->getEvent()->getName() == 'controller_isp_custom_onepage_success') {
            if ($observer->getData('0') && get_class($observer->getData('0')) == 'Hla_HlaCheckout_Block_Javascript') {
                $order = $observer->getData('0')->getOrder();
                $orderlines = $observer->getData('0')->getOrderlines();
                $cart_items = array();
                foreach($orderlines as $orderline) {
                    $product_id = Mage::getModel('catalog/product')->getIdBySku($orderline->getData('ol_prod_num'));
                    $line_item = array();
                    $line_item['product_id'] = $product_id;
                    $line_item['price'] = $orderline->getData('ol_price');
                    $line_item['quantity'] = (int)$orderline->getData('ol_qty_ord');
                    $line_item['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                    $line_item['attribution'] = $orderline->getAddedFromSearch();
                    $cart_items[] = $line_item;
                }
                return $cart_items;
            }
        }
        if ($cartItems = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems()) {
            return $this->_buildCartArray($cartItems);
        }

        return $this->_buildCartArray($this->getOrder()->getAllVisibleItems());
    }

    /**
     * Return a formatted array of quote or order items.
     *
     * @param array $cartItems
     *
     * @return array
     */
    protected function _buildCartArray($cartItems)
    {
        $items = array();
        foreach ($cartItems as $item) {
            if ($item instanceof Mage_Sales_Model_Order_Item) {
                $quantity = (int) $item->getQtyOrdered();
            } else {
                $quantity = $item->getQty();
            }
            if (is_object($item->getProduct())) {    // Fatal error fix: Call to a member function getId() on a non-object
                $items[] = array(
                    'product_id' => $item->getProduct()->getId(),
                    'price' => $item->getProduct()->getFinalPrice(),
                    'quantity' => $quantity,
                    'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                    'attribution' => $item->getAddedFromSearch(),
                );
            }
        }

        return $items;
    }

    public function catalogrule_rule_save_after($observer) {
        $nowDateGmt = Mage::getSingleton('core/date')->gmtTimestamp();

        $dt = null;

        $specialFromDate = $observer->getRule()->getFromDate();
        $specialToDate = $observer->getRule()->getToDate();

        if ($specialFromDate != null) {
            $localDate = new DateTime($specialFromDate, new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
            $specialFromDateGmt = $localDate->getTimestamp();
            if ($specialFromDateGmt && $specialFromDateGmt > $nowDateGmt) {
                $dt = $specialFromDateGmt;
            }
        }

        if ($dt == null && $specialToDate != null) {
            $localDate = new DateTime($specialToDate, new DateTimeZone(Mage::getStoreConfig('general/locale/timezone')));
            $hour = $localDate->format('H');
            $mins = $localDate->format('i');
            if ($hour == '00' && $mins == '00') {
                $localDate->modify('+86700 seconds'); //make "to" limit inclusive and another 5 minutes for safety
            }
            $specialToDateGmt = $localDate->getTimestamp();
            if ($specialToDateGmt > $nowDateGmt) {
                $dt = $specialToDateGmt;
            } else {
                return;
            }
        }
        if ($dt == null) {
            $dt = $nowDateGmt;
        }
        $affected_product_ids = Mage::getModel('Autocompleteplus_Autosuggest_Model_Rule')
            ->load($observer->getRule()->getId())
            ->getMatchingProductIds();

        foreach ($affected_product_ids as $productId=>$data) {
            $simple_product_parents = $this->batchesHelper
                ->get_parent_products_ids(intval($productId));

            $this->batchesHelper->writeProductUpdate(
                $productId,
                $dt,
                null,
                $simple_product_parents
            );
        }

    }


    public function catalogrule_rule_delete_before($observer) {
        $dt = Mage::getSingleton('core/date')->gmtTimestamp();
        $affected_product_ids = Mage::getModel('Autocompleteplus_Autosuggest_Model_Rule')
            ->load($observer->getRule()->getId())
            ->getMatchingProductIds();

        foreach ($affected_product_ids as $productId=>$data) {
            $simple_product_parents = $this->batchesHelper
                ->get_parent_products_ids(intval($productId));

            $this->batchesHelper->writeProductUpdate(
                $productId,
                $dt,
                null,
                $simple_product_parents
            );
        }

    }

}
