<?php

class Autocompleteplus_Autosuggest_Model_Resource_Pusher_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/pusher');
    }
}
