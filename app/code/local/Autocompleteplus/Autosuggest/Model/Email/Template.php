<?php

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
    const AUTOCOMPLETEPLUS_WEBHOOK_URI = 'https://sync.fastsimon.com/related_products_suggest';
    const ISP_PRODUCT_RECCOMENDATIONS = '<div id="isp-product-recs"></div>';
    const BODY_CLOSING = '</body>';

    /**
     * GetConfig returns config model
     *
     * @return false|Mage_Core_Model_Abstract
     */
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
     * @param array $variables
     * @return  string
     * @throws
     */
    public function getProcessedTemplate(array $variables = array())
    {
        $processedResult = parent::getProcessedTemplate($variables);

        if (isset($variables['this']) or !isset($variables['order'])) {
            return $processedResult;
        }

        try {
            $storeId = $variables['order']->getStoreId();
            $isEmailRecs = $this->getConfig()->getEmailRecsStatus($storeId);

            if (!$isEmailRecs) { return $processedResult; }

            // for magento < 1.8.1 use cURL directly rather than Zend or Varien
            $url = $this->getRecRequestUrl($processedResult, $variables);
            if ($url == null) {
                return $processedResult;
            }

            $client = curl_init($url);
            curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($client);

            if ($response) {
                Mage::log('Email Recommendations Response : ' . $response, null, 'autocomplete.log', true);

                $resp_obj = json_decode($response, true);
                $processedResult = $this->addRecsToEmail($resp_obj, $processedResult);
            }
            curl_close($client);
        } catch (Exception $e) {
            Mage::log(
                __FILE__.' throws:'.$e->getMessage(),
                null,
                'autocomplete.log',
                true
            );
        }

        return $processedResult;
    }

    /**
     * Get request url for product recommendations
     *
     * @param  array $variables       - variables related to order
     * @param  array $processedResult - the email template
     * @return string url
     */
    private function getRecRequestUrl($processedResult, $variables) {

        $items = array();
        foreach ($variables['order']->getItemsCollection() as $item) {
            $items[] = array(
                'product_id' => $item->getProduct()->getId(),
            );
        }

        $product_ids = array();
        foreach ($items as $item) {
            array_push($product_ids, $item['product_id']);
        }
        $products_ids_str = '[' . implode(',',$product_ids) . ']';

        if ( empty($product_ids) || !isset($product_ids[0]) || empty($product_ids[0]) ) {
            return null;
        }

        $product_id = array_values($product_ids)[0];

        $specs_sources = array(
            'similar_products_by_attributes',
            'similar_products',
            'related_views',
            'related_recently_viewed',
            'related_cart',
            'related_purchase',
            'related_top_products',
        );

        $specs = array(
            'sources' => implode(',', $specs_sources),
            'max_suggest' =>'5',
            'widget_id' => 'isp-related-widget-1',
            'title' => 'You May Also Like',
        );

        $query_arr = array(
            'store_id' => $variables['order']->getStoreId(),
            'UUID' => $this->getConfig()->getUUID(),
            'specs' => '[' . json_encode($specs) . ']',
            'product_id' => $product_id,
            'products' => $products_ids_str,
            'placement' => 'email'
        );

        $query_str = http_build_query($query_arr);

        $url = static::AUTOCOMPLETEPLUS_WEBHOOK_URI . '?' . $query_str;

        Mage::log('Email Recommendations URL: ' . $url, null, 'autocomplete.log', true);

        return $url;
    }

    /**
     * Create recommendations template and add it to the email template
     *
     * @param  array $resp_obj        - decoded recommendations response
     * @param  array $processedResult - the email template
     * @return string                 - the email template with the recommendations
     */
    private function addRecsToEmail($resp_obj, $processedResult) {

        $recs = $this->getRecsTemplate($resp_obj);

        //define snippet location
        if (strpos($processedResult, static::ISP_PRODUCT_RECCOMENDATIONS) !== false) {
            $processedResult = str_replace(
                static::ISP_PRODUCT_RECCOMENDATIONS,
                $recs,
                $processedResult
            );
        } elseif (strpos($processedResult, static::BODY_CLOSING) !== false) {
            $processedResult = str_replace(
                static::BODY_CLOSING,
                $recs,
                $processedResult);
            $processedResult  .= static::BODY_CLOSING;
        } else {
            $processedResult =  $processedResult . $recs;
        }

        return $processedResult;
    }

    /**
     * Build recommendation template
     *
     * @param  array $resp_obj        - decoded recommenations response
     * @return string                 - recommendations html
     */
    private function getRecsTemplate($resp_obj) {

        $widget_responses = $resp_obj['widget_responses'][0];
        $title = $widget_responses['title'];
        $products = $widget_responses['products'];

        if (empty($products)) {
            Mage::log('No Email Recommendations.', null, 'autocomplete.log', true);
            return "";
        }

        //set recs style
        $recs_width = 115;
        $recs_padding_left = 5;
        $recs_title_size = 12;
        $recs_title_color = '#555555';
        $recs_title_margin = 18;
        $rec_padding_bottom = 6;
        $rec_width = 100;
        $rec_height = 100;
        $recs_font = 'Arial,Helvetica,sans-serif';

        //recs table
        $recs  = '<table width="' . $recs_width . '" cellspacing="0" cellpadding="0" border="0" style="padding-left:' . $recs_padding_left . 'px">';
        $recs .= '<tbody>';

        //recs title row
        $recs .= '<tr>';
        $recs .= '<td width="'. $recs_width . '" style="vertical-align:top;">';
        $recs .= '<span style="display:inline-block;font-size:' . $recs_title_size . 'px;font-family:' . $recs_font . ';color:' . $recs_title_color . ';padding-bottom:' . $recs_title_margin . 'px;text-align:center;font-weight:bold;text-align:center">';
        $recs .= $title;
        $recs .= '</span>';
        $recs .= '</td>';
        $recs .= '</tr>';
        //recs title row ended

        //recs row
        $recs .= '<tr>';
        $recs .= '<td width="' . $recs_width . '" valign="top" >';
        $recs .= '<table width="' . $rec_width . '" border="0" cellspacing="0" cellpadding="0">';
        $recs .= '<tbody>';

        foreach ($products as $product) {
            $recs .= '<tr>';
            $recs .= '<td style="padding-bottom:' . $rec_padding_bottom . 'px;padding-left:3px" >';
            $recs .= '<a href="' . $product['u'] . '" target="_blank" >';
            $recs .= '<img height="' . $rec_height . '" width="' . $rec_width . '" alt="' . $product['l'] . '" title="' . $product['l'] . '" src="' . $product['t'] . '" class="CToWUd" style="display:block" >';
            $recs .= '</a>';
            $recs .= '</td>';
            $recs .= '</tr>';
        }

        $recs .= '</tbody>';
        $recs .= '</table>';
        $recs .= '</td>';
        $recs .= '</tr>';
        // recs row ended

        $recs .= '</tbody>';
        $recs .= '</table>';
        //recs table ended

        Mage::log('Email Recommendations Template: ' .$recs, null, 'autocomplete.log', true);

        return $recs;
    }
}