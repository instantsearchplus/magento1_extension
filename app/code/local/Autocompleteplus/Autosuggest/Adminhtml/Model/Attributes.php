<?php
/**
 * Autocompleteplus_Autosuggest_Adminhtml_Model_Attributes
 * Used in creating options for Yes|No config value selection.
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
class Autocompleteplus_Autosuggest_Adminhtml_Model_Attributes
{
    public $fields = array();
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->fields = $this->getOptions();

        return $this->fields;
    }

    public function getOptions()
    {
        $entityType = Mage::getModel('catalog/product')
            ->getResource()
            ->getEntityType();
        $entityTypeId = $entityType->getId();
        $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($entityTypeId)
            ->getData();

        $result = array();
        $result[] = array('value' => '', 'label' => 'Choose an attribute');
        foreach ($attributeInfo as $_key => $_value) {
            if (isset($_value['frontend_label']) 
                && ($_value['frontend_label'] != '')
            ) {
                $result[] = array(
                    'value' => $_value['attribute_code'],
                    'label' => $_value['frontend_label']
                );
            } else {
                $result[] = array(
                    'value' => $_value['attribute_code'],
                    'label' => $_value['attribute_code']
                );
            }
        }

        return $result;
    }
}
