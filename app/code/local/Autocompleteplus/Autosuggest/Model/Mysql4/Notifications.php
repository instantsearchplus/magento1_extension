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

class Autocompleteplus_Autosuggest_Model_Mysql4_Notifications extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('autocompleteplus_autosuggest/notifications', 'notification_id');
    }

    /**
     * @param array $notifications
     */
    public function addNotifications($notifications)
    {
        $write = $this->_getWriteAdapter();
        foreach ($notifications as $item) {
            $select = $write->select()
                ->from($this->getMainTable())
                ->where('type=?', $item['type'])
                ->where('timestamp=?', $item['timestamp']);
            $row = $write->fetchRow($select);
            if (!$row) {
                $write->insert($this->getMainTable(), $item);
            }
        }
    }
}