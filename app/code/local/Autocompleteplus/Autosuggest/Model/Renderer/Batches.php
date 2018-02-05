<?php

class Autocompleteplus_Autosuggest_Model_Renderer_Batches extends Autocompleteplus_Autosuggest_Model_Renderer_Abstract
{
    public function setXmlElement(&$xmlGenerator)
    {
        $this->_xmlElement = $xmlGenerator;

        return $this;
    }

    public function getXmlElement()
    {
        return $this->_xmlElement;
    }

    public function makeRemoveRow($batch)
    {
        $productElement = $this->getXmlElement()->createChild('product', array(
            'updatedate' => $batch['update_date'],
            'action' => $batch['action'],
            'id' => $batch['product_id'],
            'storeid' => $batch['store_id'],
        ));

        $this->getXmlElement()->createChild('sku', false, $batch['sku'], $productElement);
        $this->getXmlElement()->createChild('id', false, $batch['product_id'], $productElement);
    }
}
