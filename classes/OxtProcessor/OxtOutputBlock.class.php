<?php

require_once dirname(__FILE__) . '/OxtOutputNode.interface.php';
require_once dirname(__FILE__) . '/OxtOutputBlock.interface.php';

class OxtOutputBlock implements OxtOutputBlockInterface 
{
    // fields //
    
    protected $items = array();
    protected $index = 0;
    protected $count = 0;
    protected $finalized = false;
    protected $text = '';
    
    // Iterator //
    
    public function current()
    {
        return $this->items[$this->index];
    }
    
    public function key()
    {
        return $this->index;
    }
    
    public function next()
    {
        ++$this->index;
    }
    
    public function rewind()
    {
        $this->index = 0;
    }
    
    public function valid()
    {
        return $this->index < $this->count;
    }
    
    // OxtOutputBlockInterface //
    
    public function __construct()
    {
    }
    
    public function push (OxtOutputNodeInterface $node)
    {
        if ($this->finalized)
        {
            throw new OxtProcessorInternalException(
                'Attempt to push a node to a finalized output block',
                OxtProcessorInternalException::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->items[] = $node;
        $this->index = 0;
        ++$this->count;
        $this->text .= $node->toString();
    }
    
    public function finalize()
    {
        $this->finalized = true;
    }
    
    public function isEmpty()
    {
        return $this->count == 0;
    }

    public function toString()
    {
        return $this->text;
    }
}

