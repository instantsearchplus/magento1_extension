<?php
/**
 * InstantSearchPlus (Autosuggest).
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 *
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$helper = Mage::helper('autocompleteplus_autosuggest');

Mage::log(__FILE__.' triggered', null, 'autocomplete.log', true);

//getting site owner email
$storeMail = $helper->getConfigDataByFullPath('trans_email/ident_general/email');
Mage::log($storeMail, null, 'autocomplete.log');
Mage::getModel('core/config')->saveConfig('autocompleteplus/config/store_email', $storeMail);

Mage::getModel('core/config')->saveConfig('autocompleteplus/config/enabled', 1);
