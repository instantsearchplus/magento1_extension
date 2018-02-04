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
class Autocompleteplus_Autosuggest_SearchesController extends Mage_Core_Controller_Front_Action
{
    public function sendAction(){

        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

//        $enabled= Mage::getStoreConfig('autocompleteplus/config/enabled');
//        if($enabled=='0'){
//            die('The user has disabled autocompleteplus.');
//        }


        $startInd     = $post['offset'];
        if(!$startInd){
            $startInd=0;
        }

        $count        = $post['count'];

        //maxim products on one page is 10000
        if(!$count||$count>10000){
            $count=10000;
        }
        //retrieving page number

        //retrieving products collection to check if the offset is not bigger that the product count
        $collection=Mage::getModel('catalogsearch/query')->getCollection();

        $searchesCount=$collection->count();

        /* since the retreiving of product count will load the entire collection of products,
         *  we need to annul it in order to get the specified page only
         */
        unset($collection);

        $xml='<?xml version="1.0"?>';
        $xml.='<searches>';

        if($searchesCount<$startInd){
            //if the products count is smaller then specified offset then we return empty xml node
            $xml.='</searches>';
            echo $xml;
            die;

        }

        $connection     = $this->_getConnection('core_write');
        $sql    ="SELECT * FROM " . $this->_getTableName('catalogsearch_query'). " ORDER BY `popularity` DESC LIMIT ".$startInd.", ".$count;
        $searches=$connection->fetchAll($sql);

        //setting page+products on the page


        foreach ($searches as $search) {

            $search_term=htmlspecialchars($search['query_text']);
            $search_term=$this->_xmlEscape($search_term);
            $popularity=$search['popularity'];

            $row='<search term="'.$search_term.'" count="'.$popularity.'" ></search>';
            $xml.=$row;
        }

        $xml.='</searches>';
        
        header('Content-type: text/xml');
        echo $xml;
        die;

    }

    private function _xmlEscape($term){

        $arr=array(
            '&'=>'&amp;',
            '"'=>'&quot;',
            '<'=>'&lt;',
            '>'=>'&gt;'
        );

        foreach($arr as $key=>$val){
            $term=str_replace($key,$val,$term);
        }

        return $term;

    }

    protected function _getConnection($type = 'core_read'){
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    protected function _getTableName($tableName){
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }
}


