<?php

class Autocompleteplus_Autosuggest_Model_Mysql4_Config extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct() {
        $this->_init('autocompleteplus_autosuggest/config', 'id');
    }
}