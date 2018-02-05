<?php

$batchCollection = Mage::getModel('autocompleteplus_autosuggest/batches')
    ->getCollection();

foreach ($batchCollection as $batch) {
    $currentTime = $batch->getUpdateDate();
    $batch->setUpdateDate($currentTime)->save();
}
