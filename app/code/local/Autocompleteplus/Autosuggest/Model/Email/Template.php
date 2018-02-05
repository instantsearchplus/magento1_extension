<?php

/**
 * Template.php File
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
 * @copyright 2016 Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */

/**
 * Autocompleteplus_Autosuggest_Model_Email_Template
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
 * @copyright ${YEAR} Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */
class Autocompleteplus_Autosuggest_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    const AUTOCOMPLETEPLUS_WEBHOOK_URI = 'https://0-1ms-dot-acp-magento.appspot.com/ma_webhook';//'https://acp-magento.appspot.com/ma_webhook';

    public function getConfig()
    {
        return Mage::getModel('autocompleteplus_autosuggest/config');
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
        if ($quoteId = Mage::getSingleton('checkout/session')->getQuoteId()
        ) {
            return $quoteId;
        }

        return $this->getOrder()->getQuoteId();
    }

    /**
     * Process email template code
     *
     * @param   array $variables
     * @return  string
     */
    public function getProcessedTemplate($variables)
    {
        $processedResult = parent::getProcessedTemplate($variables);

        if (!isset($variables['this']) && isset($variables['order'])) {
            try {
                $items = array();
                foreach ($variables['order']->getItemsCollection() as $item) {
                    $items[] = array(
                        'product_id' => $item->getProduct()->getId(),
                        'price' => $item->getrow_total(),
                        'quantity' => $item->getqty_ordered(),
                        'currency' => $variables['order']->getorder_currency_code(),
                        'attribution' => $item->getAddedFromSearch(),
                    );
                }

                $items_json = json_encode($items);

                $params = array(
                    'template' => $processedResult,
                    'event' => 'email_confirmation',
                    'UUID' => $this->getConfig()->getUUID(),
                    'key' => $this->getConfig()->getAuthorizationKey(),
                    'store_id' => $variables['order']->getStoreId(),
                    'st' => Mage::helper('autocompleteplus_autosuggest')
                        ->getSessionId(),
                    'cart_token' => $variables['order']->getQuoteId(),
                    'cart_product' => $items_json
                );

                // @codingStandardsIgnoreStart
                /**
                 * Due to backward compatibility issues with Magento < 1.8.1 and cURL/Zend
                 * We need to use PHP's implementation of cURL directly rather than Zend or Varien.
                 */
                $client = curl_init(static::AUTOCOMPLETEPLUS_WEBHOOK_URI);
                curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($client, CURLOPT_POSTFIELDS, $params);
                $response = curl_exec($client);
                curl_close($client);
            } catch (Exception $e) {
                Mage::log(
                    __FILE__.' throws:'.$e->getMessage(),
                    null,
                    'autocomplete.log',
                    true
                );
            }
        }

        return $processedResult;
    }
}