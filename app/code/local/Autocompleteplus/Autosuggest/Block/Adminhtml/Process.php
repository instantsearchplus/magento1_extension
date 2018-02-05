<?php
/**
 * Autocompleteplus_Autosuggest_Block_Adminhtml_Process
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
class Autocompleteplus_Autosuggest_Block_Adminhtml_Process extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        $configTemplate = <<<'TMPL'
<li style="#{style}" id="#{id}">
<img id="#{id}_img" src="#{image}" class="v-middle" style="margin-right:5px"/>
<span id="#{id}_status" class="text">#{text}</span>
</li>
TMPL;

        $this->_pushConfig = array(
            'styles' => array(
                'error' => array(
                    'icon' => $this->getSkinUrl('images/error_msg_icon.gif'),
                    'bg' => '#FDD',
                ),
                'message' => array(
                    'icon' => $this->getSkinUrl('images/fam_bullet_success.gif'),
                    'bg' => '#DDF',
                ),
            ),
            'loader' => $this->getSkinUrl('images/ajax-loader.gif'),
            'template' => $configTemplate,
            'text' => $this->__(
                'Processed <strong>%s%% %s/%d</strong> records',
                '#{percent}',
                '#{updated}',
                $this->getBatchItemsCount()
            ),
            'successText' => $this->__(
                'Imported <strong>%s</strong> records',
                '#{updated}'
            ),
        );
    }
}
