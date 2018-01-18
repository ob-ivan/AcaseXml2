<?php

require_once dirname(__FILE__) . '/OxtCodeBlock.interface.php';
require_once dirname(__FILE__) . '/OxtCodeNode.class.php';

class OxtCodeBlock implements OxtCodeBlockInterface
{
    // fields //
    
    protected $items = array();
    protected $index = 0;
    protected $count = 0;
    protected $finalized = false;
    
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
    
    // OxtCodeBlockInterface //
    
    public function __construct()
    {
    }
    
    public function push (OxtCodeNodeInterface $node)
    {
        if ($this->finalized)
        {
            throw new OxtProcessorInternalException(
                'Attempt to push a node to a finalized code block',
                OxtProcessorInternalException::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->items[] = $node;
        $this->index = 0;
        ++$this->count;
    }
    
    public function finalize()
    {
        $this->finalized = true;
    }
    
    public function finalization()
    {
        $clone = new self();
        foreach ($this->items as $node)
        {
            $clone->push($node);
        }
        $clone->finalize();
        return $clone;
    }
    
    public function isEmpty()
    {
        return $this->count == 0;
    }
    
    public function getCount()
    {
        return $this->count;
    }
    
    public function getItem ($index)
    {
        $index = intval ($index);
        if ($index > $this->count)
        {
            throw new OxtProcessorInternalException(
                'Undefined code block offset: ' . $index . '; count = ' . $this->count . ' (apriori)',
                OxtProcessorInternalException::CODE_UNDEFINED_OFFSET
            );
        }
        $i = 0;
        foreach ($this->items as $item)
        {
            if ($i == $index)
            {
                return $item;
            }
            ++$i;
        }
        throw new OxtProcessorInternalException(
            'Undefined code block offset: ' . $index . '; count = ' . $this->count . ' (aposteriori)',
            OxtProcessorInternalException::CODE_UNDEFINED_OFFSET
        );
    }
}
