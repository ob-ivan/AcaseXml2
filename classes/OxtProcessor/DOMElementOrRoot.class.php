<?php

require_once dirname(__FILE__) . '/DOMElementOrRoot.interface.php';

class DOMElementOrRoot implements DOMElementOrRootInterface
{
    // fields //
    
    protected $element;
    
    // DOMElementOrRootInterface //
    
    /**
     * null -- специальное значение для обозначения корня.
    **/
    public function __construct (DOMElement $element = null)
    {
        $this->element = $element;
    }
    
    public function isRoot()
    {
        return $this->element == null;
    }
    
    public function getElement()
    {
        return $this->element;
    }
}
