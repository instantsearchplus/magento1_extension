<?php
/**
 * Autocompleteplus_Autosuggest_Block_Notifications
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category Mage
 *
 * @package   Instantsearchplus
 * @author    Fast Simon <info@instantsearchplus.com>
 * @copyright 2014 Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */
class Autocompleteplus_Autosuggest_Block_Notifications extends Mage_Core_Block_Template
{
    /**
     * @return Autocompleteplus_Autosuggest_Model_Mysql4_Notifications_Collection
     */
    public function getNotifications()
    {
        /** 
         * @var Autocompleteplus_Autosuggest_Model_Mysql4_Notifications_Collection $collection 
         */
        $collection = Mage::getModel('autocompleteplus_autosuggest/notifications')
            ->getCollection();

        return $collection->addTypeFilter('alert')->addActiveFilter();
    }

    /**
     * Checks if localhost synced
     * 
     * @return bool
     */
    public function localhostSynced()
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');

        $isReachable = $helper->getIsReachable();

        $syncWasStarted = $helper->getIfSyncWasInitiated();

        if (!$isReachable && !$syncWasStarted) {
            return true;
        } else {
            return false;
        }
    }
}
