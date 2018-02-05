<?php
/**
 * Catalogsearch.php File
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
 
 class Autocompleteplus_Autosuggest_Helper_Catalogsearch extends Mage_CatalogSearch_Helper_Data
 {
     public function getResultUrl($query = null)
     {
         try {
             $layered = Mage::getStoreConfig('autocompleteplus/config/miniform_change');
         } catch (Exception $e) {
             Mage::log('ResultController::indexAction() exception: '.$e->getMessage(), null, 'autocompleteplus.log');
         }
         if (isset($layered) && $layered == 1) {
             return $this->_getUrl('instantsearchplus/result', array(
                 '_query' => array(self::QUERY_VAR_NAME => $query),
                 '_secure' => $this->_getApp()->getFrontController()->getRequest()->isSecure()
             ));
             
         } else {
             return $this->_getUrl('catalogsearch/result', array(
                 '_query' => array(self::QUERY_VAR_NAME => $query),
                 '_secure' => $this->_getApp()->getFrontController()->getRequest()->isSecure()
             ));
             
         }
     }
 }