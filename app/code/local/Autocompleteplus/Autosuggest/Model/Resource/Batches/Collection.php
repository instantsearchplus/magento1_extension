<?php

class Autocompleteplus_Autosuggest_Model_Resource_Batches_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/batches');
    }
}
