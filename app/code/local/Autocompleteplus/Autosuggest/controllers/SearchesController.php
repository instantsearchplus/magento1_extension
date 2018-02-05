<?php
/**
 * SearchesController File
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

/**
 * Autocompleteplus_Autosuggest_SearchesController
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
class Autocompleteplus_Autosuggest_SearchesController extends Mage_Core_Controller_Front_Action
{
    /**
     * Sends searches of the magento at extension install
     *
     * @return void
     */
    public function sendAction()
    {
        $post = $this->getRequest()->getParams();

        $response = $this->getResponse();

        $startInd = $post['offset'];
        if (!$startInd) {
            $startInd = 0;
        }

        $count = $post['count'];

        //maxim products on one page is 10000
        if (!$count || $count > 10000) {
            $count = 10000;
        }
        //retrieving page number

        /**
         * Retrieving products collection to check
         * if the offset is not bigger that the product count
         */
        $collection = Mage::getModel('catalogsearch/query')->getCollection()
            ->setOrder('popularity', 'DESC');

        $collection->getSelect()->limit($count, $startInd);

        $searchesCount = $collection->getSize();

        /**
         * Since the retreiving of product count
         * will load the entire collection of products,
         *  we need to annul it in order to get the specified page only
         */

        $xml = '<?xml version="1.0"?>';
        $xml .= '<searches>';

        if ($searchesCount < $startInd) {
            $xml .= '</searches>';

            $response->clearHeaders();
            $response->setHeader('Content-type', 'text/xml');
            $response->setBody($xml);

            return;
        }

        foreach ($collection as $search) {
            $search_term = htmlspecialchars($search->getData('query_text'));
            $search_term = $this->xmlEscape($search_term);
            $popularity = $search->getData('popularity');

            $row = '<search term="'.
                       $search_term.'" count="'.$popularity.'" ></search>';
            $xml .= $row;
        }

        $xml .= '</searches>';

        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/xml');
        $response->setBody($xml);
    }

    /**
     * Clear xml special chars
     *
     * @param mixed $term
     *
     * @return mixed
     */
    protected function xmlEscape($term)
    {
        $arr = array(
            '&' => '&amp;',
            '"' => '&quot;',
            '<' => '&lt;',
            '>' => '&gt;',
        );

        foreach ($arr as $key => $val) {
            $term = str_replace($key, $val, $term);
        }

        return $term;
    }

    /**
     * Get db connection object
     *
     * @param string $type
     *
     * @return mixed
     */
    protected function _getConnection($type = 'core_read')
    {
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    /**
     * Get table name
     *
     * @param string $tableName
     *
     * @return mixed
     */
    protected function _getTableName($tableName)
    {
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }
}
