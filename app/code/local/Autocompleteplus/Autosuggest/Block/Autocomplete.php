<?php
/**
 * Autocompleteplus_Autosuggest_Block_Autocomplete
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
class Autocompleteplus_Autosuggest_Block_Autocomplete extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $is_full_text_wrong_flow = false;
        try {
            $fulltext_enable = Mage::getSingleton('core/session')
                ->getIsFullTextEnable();
            if (!$fulltext_enable) {
                return '';
            }
            /** 
             * Checking if the searched query from addSearchFilter() 
             * is the same to the current query
             */ 
            $current_search_term = urlencode(self::getRequest()->getParam('q'));
            $searched_term = Mage::getSingleton('core/session')
                ->getIspUrlEncodeQuery();
            if ($current_search_term != $searched_term) {
                $is_full_text_wrong_flow = true;
            }
        } catch (Exception $e) {
            Mage::log('autocomplete.php _toHtml() Exception => fulltext_enable and query calculations'.$e->getMessage(), null, 'autocompleteplus.log');

            return '';
        }

        $params = array();
        if (!$is_full_text_wrong_flow) {
            try {
                $alternatives = Mage::getSingleton('core/session')
                    ->getIspSearchAlternatives();
                $results_for = Mage::getSingleton('core/session')
                    ->getIspSearchResultsFor();
            } catch (Exception $e) {
                Mage::log(
                    'autocomplete.php _toHtml() Exception => results_for and did you mean calculations'.$e->getMessage(),
                    null,
                    'autocompleteplus.log'
                );

                return '';
            }

            if ($alternatives) {
                $params['alternatives'] = json_encode($alternatives);
            }
            if ($results_for) {
                $params['results_for'] = $results_for;
            }
        } else {
            $params['wrong_flow'] = 1;
        }

        $magento_version = Mage::getVersion();
        $extension_version = (string) Mage::getConfig()
            ->getNode()
            ->modules
            ->Autocompleteplus_Autosuggest
            ->version;
        $versions = ($params == '') ? 'mage_v='.$magento_version.'&ext_v='.$extension_version
                                    : '&mage_v='.$magento_version.'&ext_v='.$extension_version;

        Mage::getSingleton('core/session')->unsIsFullTextEnable();
        Mage::getSingleton('core/session')->unsIspSearchAlternatives();
        Mage::getSingleton('core/session')->unsIspSearchResultsFor();

        return '<script data-cfasync="false" async type="text/javascript" src="https://acp-magento.appspot.com/js/magento_full_text.js?'.http_build_query($params).$versions.'"></script>';
    }
}
