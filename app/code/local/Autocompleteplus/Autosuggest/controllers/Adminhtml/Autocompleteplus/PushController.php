<?php

class Autocompleteplus_Autosuggest_Adminhtml_Autocompleteplus_PushController extends Mage_Adminhtml_Controller_Action
{
    public function startpushAction()
    {
        $response = $this->getResponse();

        $service = Mage::getModel('autocompleteplus_autosuggest/service');
        $service->populatePusher();

        $response->setBody($this->getLayout()->createBlock('autocompleteplus_autosuggest/adminhtml_process')->toHtml());
        $response->sendResponse();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/autocompleteplus');
    }

}