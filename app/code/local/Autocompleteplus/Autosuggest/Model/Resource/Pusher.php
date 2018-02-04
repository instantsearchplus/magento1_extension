<?php

class Autocompleteplus_Autosuggest_Model_Resource_Pusher extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/pusher', 'id');
    }

    public function truncate()
    {
        $this->_getWriteAdapter()->query('TRUNCATE TABLE '.$this->getMainTable());

        return $this;
    }
}
