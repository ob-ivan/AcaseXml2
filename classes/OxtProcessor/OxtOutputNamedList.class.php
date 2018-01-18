<?php

require_once dirname(__FILE__) . '/OxtOutputBlock.interface.php';
require_once dirname(__FILE__) . '/OxtOutputNamedList.interface.php';

class OxtOutputNamedList implements OxtOutputNamedListInterface
{
    // fields //
    
    protected $items = array();
    protected $finalized = false;
    
    // OxtOutputNamedListInterface //
    
    public function __constructor()
    {
    }
    
    public function set ($itemName, OxtOutputBlockInterface $itemValue)
    {
        if ($this->finalized)
        {
            throw new OxtProcessorInternalException(
                'Attempt to set a named node in a finalized output list',
                OxtProcessorInternalException::CODE_PUSH_TO_FINALIZED
            );
        }
        $this->items[$itemName] = $itemValue;
    }
    
    public function finalize()
    {
        $this->finalized = true;
    }
    
    public function get ($itemName)
    {
        return $this->items[$itemName];
    }
}
