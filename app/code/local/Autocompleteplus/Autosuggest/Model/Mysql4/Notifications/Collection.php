<?php
/**
 * InstantSearchPlus (Autosuggest)

 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Mage
 * @package    InstantSearchPlus
 * @copyright  Copyright (c) 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Autocompleteplus_Autosuggest_Model_Mysql4_Notifications_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/notifications');
    }

    /**
     * @param string $type
     * @return $this
     */
    public function addTypeFilter($type)
    {
        $this->getSelect()
            ->where('type=?', $type);
        return $this;
    }

    public function addActiveFilter()
    {
        $this->getSelect()
            ->where('is_active=?', 1);
        return $this;
    }
}