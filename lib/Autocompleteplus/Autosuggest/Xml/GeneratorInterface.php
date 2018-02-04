<?php

interface Autocompleteplus_Autosuggest_Xml_GeneratorInterface
{
    public function getSimpleXml();

    public function getRootElementName();

    public function setRootElementName($name);

    public function generateXml();
}