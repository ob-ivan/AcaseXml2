<?php

require_once dirname(__FILE__) . '/OxtOperatorList.interface.php';
require_once dirname(__FILE__) . '/OxtOperator.interface.php';

class OxtOperatorList implements OxtOperatorListInterface
{
    // fields //
    
    protected $items = array();
    protected $index = 0;
    protected $count = 0;
    
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
    
    // OxtOperatorListInterface //
    
    public function __construct()
    {
    }
    
    public function push (OxtOperatorInterface $operator)
    {
        $this->items[] = $operator;
        usort ($this->items, __CLASS__ . '::comparePriority');
        $this->index = 0;
        ++$this->count;
    }
    
    // protected //
    
    protected static function comparePriority (OxtOperatorInterface $a, OxtOperatorInterface $b)
    {
        return $b->getPriority() - $a->getPriority();
    }
}
