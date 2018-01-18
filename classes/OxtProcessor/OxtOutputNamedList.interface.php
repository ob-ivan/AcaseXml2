<?php

require_once dirname(__FILE__) . '/OxtOutputBlock.interface.php';

interface OxtOutputNamedListInterface
{
    public function __constructor();
    
    public function set ($itemName, OxtOutputBlockInterface $itemValue);
    
    public function finalize();
    
    public function get ($itemName);
}
