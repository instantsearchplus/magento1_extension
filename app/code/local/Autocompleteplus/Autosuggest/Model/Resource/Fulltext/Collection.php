<?php
/**
 * Magento.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 *
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Fulltext Collection.
 *
 * @category    Mage
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Autocompleteplus_Autosuggest_Model_Resource_Fulltext_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    protected $list_ids = array();
    protected $is_fulltext_enabled = false;

    protected $is_layered_enabled = false;

    public function __construct($resource = null, array $args = array())
    {
        $layered = Mage::getStoreConfig('autocompleteplus/config/layered');
        if (isset($layered) && $layered == '1') {
            $this->is_layered_enabled = true;
        }
        parent::__construct($resource, $args);
    }

    /**
     * Retrieve query model object.
     *
     * @return Mage_CatalogSearch_Model_Query
     */
    protected function _getQuery()
    {
        if (!$this->is_layered_enabled) {
            return Mage::helper('catalogsearch')->getQuery();
        }

        return false;
    }

    /* compatibility with GoMage extension */
    public function getSearchedEntityIds()
    {
        return $this->list_ids;
    }

    protected function getSession()
    {
        return Mage::getSingleton('core/session');
    }

    public function clearSessionData()
    {
        $this->getSession()->unsIsFullTextEnable();
        $this->getSession()->unsIspSearchAlternatives();
        $this->getSession()->unsIspSearchResultsFor();

        return $this;
    }

    public function setSessionData($responseObj, $query)
    {
        // InstantSearch+ js file will be injected to the search result page
        $this->getSession()->setIsFullTextEnable(true);
        // recording the query for the current 'core/session' to check it when injecting the magento_full_text.js
        $this->getSession()->setIspUrlEncodeQuery(urlencode($query));

        if (array_key_exists('alternatives', $responseObj) && $responseObj->alternatives) {
            $this->getSession()->setIspSearchAlternatives($responseObj->alternatives);
        } else {
            $this->getSession()->setIspSearchAlternatives(false);
        }

        if (array_key_exists('results_for', $responseObj) && $responseObj->results_for) {
            $this->getSession()->setIspSearchResultsFor($responseObj->results_for);
        } else {
            $this->getSession()->setIspSearchResultsFor(false);
        }
    }

    public function prepareDefaultQuery()
    {
        //adding if fulltext search disabled then write regular flow
        Mage::getSingleton('catalogsearch/fulltext')->prepareResult();

        $this->getSelect()->joinInner(
            array('search_result' => $this->getTable('catalogsearch/result')),
            $this->getConnection()->quoteInto(
                'search_result.product_id=e.entity_id AND search_result.query_id=?',
                $this->_getQuery()->getId()
            ),
            array('relevance' => 'relevance')
        );
    }

    /**
     * Add search query filter.
     *
     * @param string $query
     *
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function addSearchFilter($query)
    {
        if (!$this->is_layered_enabled) {
            $helper = Mage::helper('autocompleteplus_autosuggest');
            $config = Mage::getModel('autocompleteplus_autosuggest/config');
            $key = $config->getUUID();
            $storeId = Mage::app()->getStore()->getStoreId();
            $server_end_point = $helper->getServerEndPoint();

            $url_domain = $server_end_point ? $server_end_point.'/ma_search' : 'http://magento.instantsearchplus.com/ma_search';

            $extension_version = Mage::helper('autocompleteplus_autosuggest')->getVersion();
            $site_url = $helper->getConfigDataByFullPath('web/unsecure/base_url');
            $url = $url_domain.'?q='.urlencode($query).'&p=1&products_per_page=1000&v='.$extension_version.'&store_id='.$storeId.'&UUID='.$key.'&h='.$site_url;

            $resp = $helper->sendCurl($url);
            $response_json = json_decode($resp);
            $enabledFulltext = array_key_exists('fulltext_disabled', $response_json) ? !$response_json->fulltext_disabled : false;

            if ($enabledFulltext) {
                $enabledFulltext = ((array_key_exists('id_list', $response_json)) &&
                    (array_key_exists('total_results', $response_json))) ? true : false;
            }

            $this->clearSessionData();

            if (!$enabledFulltext) {
                Mage::getSingleton('core/session')->setIsFullTextEnable(false);

                $this->prepareDefaultQuery();
            }

            if ($enabledFulltext) {
                $this->is_fulltext_enabled = true;
                $this->setSessionData($response_json, $query);

                if ($response_json->total_results) {
                    $id_list = $response_json->id_list;
                    $product_ids = array();
                    //validate received ids
                    foreach ($id_list as $id) {
                        if ($id != null && is_numeric($id)) {
                            $product_ids[] = $id;
                        }
                    }
                    $this->list_ids = $product_ids;
                    $idStr = (count($product_ids) > 0) ? implode(',', $product_ids) : '0';
                } else {
                    $idStr = '0';
                }

                if (array_key_exists('server_endpoint', $response_json)) {
                    if ($server_end_point != $response_json->server_endpoint) {
                        $helper->setServerEndPoint($response_json->server_endpoint);
                    }
                }

                $this->getSelect()->where('e.entity_id IN ('.$idStr.')');
            }
        }

        return $this;
    }

    /**
     * Set Order field.
     *
     * @param string $attribute
     * @param string $dir
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function setOrder($attribute, $dir = parent::SORT_ORDER_ASC)
    {
        if (!$this->is_layered_enabled) {
            if ($this->is_fulltext_enabled && $attribute == 'relevance') {
                $dir = parent::SORT_ORDER_ASC;
                $id_str = (count($this->list_ids) > 0) ? implode(',', $this->list_ids) : '0';
                if (!empty($id_str)) {
                    $sort = "FIELD(e.entity_id, {$id_str}) {$dir}";
                    $this->getSelect()->order(new Zend_Db_Expr($sort));
                }
            } else {
                return parent::setOrder($attribute, $dir);
            }
        }

        return $this;
    }

    /**
     * Stub method for campatibility with other search engines.
     *
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function setGeneralDefaultQuery()
    {
        return $this;
    }
}
