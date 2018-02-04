<?php

class Autocompleteplus_Autosuggest_Model_Resource_Checksum_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/checksum');
    }
}
