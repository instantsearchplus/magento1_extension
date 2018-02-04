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
class Autocompleteplus_Autosuggest_CategoriesController extends Mage_Core_Controller_Front_Action
{
    public function sendAction()
    {
        $categories = $this->loadTree();

        $response = $this->getResponse();
        $response->clearHeaders();
        $response->setHeader('Content-type', 'application/json');

        $response->setBody(json_encode($categories));
    }

    protected function nodeToArray(Varien_Data_Tree_Node $node, $mediaUrl, $baseUrl, $store)
    {
        $thumbnail = '';

        try {
            if ($thumbImg = $node->getThumbnail()) {
                $thumbnail = sprintf('%scatalog/category/%s', $mediaUrl, $node->getThumbnail());
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $category = Mage::getModel('catalog/category')->setStoreId($store)->load($node->getId());

        $result = array(
            'category_id' => $node->getId(),
            'image' => sprintf('%scatalog/category/%s', $mediaUrl, $node->getImage()),
            'thumbnail' => $thumbnail,
            'description' => strip_tags($node->getDescription()),
            'parent_id'   => $node->getParentId(),
            'name'        => $node->getName(),
            'url_path'    => $category->getUrl(),
            'is_active'   => $node->getIsActive(),
            'children'    => array(),
        );

        foreach ($node->getChildren() as $child) {
            $result['children'][] = $this->nodeToArray($child, $mediaUrl, $baseUrl, $store);
        }

        return $result;
    }

    protected function loadTree()
    {
        $storeContext = Mage::app()->getStore()->getStoreId();
        $tree = Mage::getResourceSingleton('catalog/category_tree')->load();
        $store = $this->getRequest()->getParam('store', $storeContext);
        $parentId = Mage::app()->getStore($store)->getRootCategoryId();

        $root = $tree->getNodeById($parentId);

        if ($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }

        $collection = Mage::getModel('catalog/category')->getCollection()
            ->setStoreId($store)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('url_path')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('thumbnail')
            ->addAttributeToSelect('description')
            ->addAttributeToFilter('is_active', array('eq' => true));

        $tree->addCollectionData($collection, true);

        $mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        return $this->nodeToArray($root, $mediaUrl, $baseUrl, $store);
    }
}
