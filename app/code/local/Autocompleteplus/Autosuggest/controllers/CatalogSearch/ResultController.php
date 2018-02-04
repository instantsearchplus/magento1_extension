<?php
require_once 'Mage/CatalogSearch/controllers/ResultController.php';

class Autocompleteplus_Autosuggest_CatalogSearch_ResultController extends Mage_CatalogSearch_ResultController
{
    public function indexAction() {
        try {
            $layered = Mage::getStoreConfig('autocompleteplus/config/layered');
        } catch (Exception $e) {
            Mage::log('ResultController::indexAction() exception: ' . $e->getMessage(),null,'autocompleteplus.log');
        }
        if (isset($layered) && $layered == 1) {
            $this->loadLayout();
            $this->renderLayout();
        } else {
            parent::indexAction();
        }
    }
}