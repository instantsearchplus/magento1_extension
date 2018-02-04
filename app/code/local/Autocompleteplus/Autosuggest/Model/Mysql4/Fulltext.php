<?php
/**
 * InstantSearchPlus (Autosuggest)

 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 * @package    InstantSearchPlus
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Autocompleteplus_Autosuggest_Model_Mysql4_Fulltext extends Mage_CatalogSearch_Model_Mysql4_Fulltext
{
    /**
     * Prepare results for query
     *
     * @param Mage_CatalogSearch_Model_Fulltext $object
     * @param string $queryText
     * @param Mage_CatalogSearch_Model_Query $query
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext
     */
    public function prepareResult($object, $queryText, $query)
    {
        $optimDisabled= Mage::getStoreConfig('autocompleteplus/config/searchoptim');
    
        if (!$query->getIsProcessed()) {
            $searchType = $object->getSearchType($query->getStoreId());

            $stringHelper = Mage::helper('core/string');
            /* @var $stringHelper Mage_Core_Helper_String */

            $bind = array(
                ':query' => $queryText
            );
            $like = array();

            $fulltextCond   = '';
            $likeCond       = '';
            $separateCond   = '';

            if ($searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_LIKE
                || $searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_COMBINE) {
                $words = $stringHelper->splitWords($queryText, true, $query->getMaxQueryWords());
                $likeI = 0;
                foreach ($words as $word) {
                    $like[] = '`s`.`data_index` LIKE :likew' . $likeI;
                    $bind[':likew' . $likeI] = '%' . $word . '%';
                    $likeI ++;
                }
                if ($like) {
                    $likeCond = '(' . join(' AND ', $like) . ')';
                }
            }
            if ($searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_FULLTEXT
                || $searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_COMBINE) {
                $fulltextCond = 'MATCH (`s`.`data_index`) AGAINST (:query IN BOOLEAN MODE)';
            }
            if ($searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_COMBINE && $likeCond) {
                $separateCond = ' OR ';
            }

            if($optimDisabled==1){ 
                  $sql = sprintf("INSERT INTO `{$this->getTable('catalogsearch/result')}` "
                    . "(SELECT '%d', `s`.`product_id`, MATCH (`s`.`data_index`) AGAINST (:query IN BOOLEAN MODE) "
                    . "FROM `{$this->getMainTable()}` AS `s` INNER JOIN `{$this->getTable('catalog/product')}` AS `e`"
                    . "ON `e`.`entity_id`=`s`.`product_id` WHERE (%s%s%s) AND `s`.`store_id`='%d')"
                    . " ON DUPLICATE KEY UPDATE `relevance`=VALUES(`relevance`)",
                    $query->getId(),
                    $fulltextCond,
                    $separateCond,
                    $likeCond,
                    $query->getStoreId()
                );
            }else{
              $sql = sprintf("INSERT INTO `{$this->getTable('catalogsearch/result')}` "
                  . "(SELECT STRAIGHT_JOIN '%d', `s`.`product_id`, MATCH (`s`.`data_index`) "
                  . "AGAINST (:query IN BOOLEAN MODE) FROM `{$this->getMainTable()}` AS `s` "
                  . "INNER JOIN `{$this->getTable('catalog/product')}` AS `e` "
                  . "ON `e`.`entity_id`=`s`.`product_id` WHERE (%s%s%s) AND `s`.`store_id`='%d')"
                  . " ON DUPLICATE KEY UPDATE `relevance`=VALUES(`relevance`)",
                  $query->getId(),
                  $fulltextCond,
                  $separateCond,
                  $likeCond,
                  $query->getStoreId()
              );
            }

            $this->_getWriteAdapter()->query($sql, $bind);

            $query->setIsProcessed(1);
        }

        return $this;
    }
}
