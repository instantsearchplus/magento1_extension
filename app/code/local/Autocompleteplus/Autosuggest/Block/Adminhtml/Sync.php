<?php

/**
 * Yes/no options for sync
 */
class Autocompleteplus_Autosuggest_Block_Adminhtml_Sync extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autocompleteplus/system/config/sync.phtml');
    }

    /**
     *
     * Return element html
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return UUID
     * @return string
     */
    public function getUUID()
    {
        $helper=Mage::helper('autocompleteplus_autosuggest');
        return $helper->getUUID();
    }

    /**
     * Reachable or not
     * @return bool
     */
    public function getIsReachable()
    {
        $helper=Mage::helper('autocompleteplus_autosuggest');
        return $helper->getIsReachable();
    }

    /**
     * Return ajax url for button
     * @return string
     */
    public function getSyncUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/autocompleteplus_push/startpush');
    }

    /**
     * Generate button html
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'autocompleteplus_sync',
                'label'     => $this->helper('adminhtml')->__('Sync'),
                'onclick'   => 'javascript:syncautocomplete(); return false;'
            ));

        return $button->toHtml();
    }
}