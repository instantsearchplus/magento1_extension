<?php

class Autocompleteplus_Autosuggest_Adminhtml_Autocompleteplus_RedirectController extends Mage_Adminhtml_Controller_Action
{
    const ISP_SITE_URL = 'https://magento.instantsearchplus.com/';
    const REDIRECT_STATUS_CODE = 302;

    protected function _construct()
    {
        $this->helper = Mage::helper('autocompleteplus_autosuggest');
    }

    public function gotoAction()
    {
        $kwys     = $this->helper->getBothKeys();
        $response = $this->getResponse();

        $response->clearHeaders();
        $response->setRedirect($this->_getIspLoginUrl($kwys), self::REDIRECT_STATUS_CODE);
        $response->sendResponse();
    }

    protected function _getIspLoginUrl($kwys)
    {
        $uuid = $kwys['uuid'];
        $authkey = $kwys['authkey'];

        if(!isset($uuid) || !isset($authkey)){
           return self::ISP_SITE_URL . 'login';
        }

        return self::ISP_SITE_URL . "ma_dashboard?site_id=$uuid&authentication_key=$authkey";
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/autocompleteplus');
    }
}