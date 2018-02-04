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

class Autocompleteplus_Autosuggest_CategoriesController extends Mage_Core_Controller_Front_Action
{
    public function sendAction(){

        $categories=$this->load_tree();

        echo json_encode($categories);
    }

    private function nodeToArray(Varien_Data_Tree_Node $node , $mediaUrl, $baseUrl, $store)
    {
        $result = array();

        $thumbnail='';

        try{

            $thumbImg=$node->getThumbnail();

           if($thumbImg!=null){

              $thumbnail=$mediaUrl.'catalog/category/'.$node->getThumbnail();
           }
        }catch(Exception $e){

        }

        $result['category_id'] = $node->getId();
        $result['image'] = $mediaUrl.'catalog/category/'.$node->getImage();
        $result['thumbnail'] = $thumbnail;
        $result['description'] = strip_tags($node->getDescription());
        $result['parent_id'] = $node->getParentId();
        $result['name'] = $node->getName();
        $result['is_active'] = $node->getIsActive();
        $result['children'] = array();
        
        if (method_exists('Mage' , 'getEdition') && Mage::getEdition() == Mage::EDITION_COMMUNITY){
//             $result['url_path'] = $baseUrl.$node->getData('url_path');
            
            $category = Mage::getModel('catalog/category')->setStoreId($store)->load($node->getId());
            $result['url_path'] = $category->getUrl();
        } else {
            $category = Mage::getModel('catalog/category')->setStoreId($store)->load($node->getId());
            $result['url_path'] = $category->getUrl();
        }
          
        foreach ($node->getChildren() as $child) {
            $result['children'][] = $this->nodeToArray($child,$mediaUrl,$baseUrl, $store);
        }

        return $result;
    }

    private function load_tree(){

        $tree = Mage::getResourceSingleton('catalog/category_tree')
        ->load();

        $post = $this->getRequest()->getParams();

        if (array_key_exists('store', $post))
            $store = $post['store'];
        else
            $store = Mage::app()->getStore()->getStoreId();
                
        $parentId =  Mage::app()->getStore($store)->getRootCategoryId();

        $tree = Mage::getResourceSingleton('catalog/category_tree')
        ->load();

        $root = $tree->getNodeById($parentId);

        if($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }

        $collection = Mage::getModel('catalog/category')->getCollection()
                    ->setStoreId($store)
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('url_path')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('thumbnail')
                    ->addAttributeToSelect('description')
            ->addAttributeToFilter('is_active',array('eq'=>true));

        $tree->addCollectionData($collection, true);

        $mediaUrl= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $baseUrl= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        return $this->nodeToArray($root,$mediaUrl,$baseUrl, $store);

    }

}