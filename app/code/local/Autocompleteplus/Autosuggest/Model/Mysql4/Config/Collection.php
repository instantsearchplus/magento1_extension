<?php

class Autocompleteplus_Autosuggest_Model_Mysql4_Config_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct() {
        $this->_init('autocompleteplus_autosuggest/config');
    }
}