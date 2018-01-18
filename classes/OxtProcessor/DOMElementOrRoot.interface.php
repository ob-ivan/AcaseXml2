<?php

interface DOMElementOrRootInterface
{
    /**
     * null -- специальное значение для обозначения корня.
    **/
    public function __construct (DOMElement $element = null);
    
    public function isRoot();
    
    public function getElement();
}
