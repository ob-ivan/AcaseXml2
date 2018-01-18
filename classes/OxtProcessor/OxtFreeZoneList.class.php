<?php

require_once dirname(__FILE__) . '/OxtFreeZoneList.interface.php';

class OxtFreeZoneList implements OxtFreeZoneListInterface
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
    
    // OxtFreeZoneListInterface //
    
    public function __construct()
    {
    }
    
    public function push (OxtFreeZoneInterface $freeZone)
    {
        if ($this->finalized)
        {
            throw new OxtExceptionInternal(
                'Attempt to push a node to a finalized free zone list',
                OxtExceptionInternal::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->items[] = $freeZone;
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
    
    public function getCount()
    {
        return $this->count;
    }
}
