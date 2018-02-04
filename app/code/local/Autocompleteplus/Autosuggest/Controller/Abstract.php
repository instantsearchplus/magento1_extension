<?php

abstract class Autocompleteplus_Autosuggest_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    const PHP_SCRIPT_TIMEOUT = 1800;
    const MISSING_PARAMETER = 767;
    const STATUS_FAILURE = 'failure';

    public function preDispatch()
    {
        parent::preDispatch();
        set_time_limit(self::PHP_SCRIPT_TIMEOUT);
    }
}
