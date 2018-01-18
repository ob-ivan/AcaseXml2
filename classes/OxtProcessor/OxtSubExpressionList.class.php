<?php

require_once dirname(__FILE__) . '/OxtSubExpressionList.interface.php';
require_once dirname(__FILE__) . '/OxtSubExpression.interface.php';

class OxtSubExpressionList implements OxtSubExpressionListInterface
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
    
    // OxtSubExpressionListInterface //
    
    public function __construct()
    {
    }
    
    public function push (OxtSubExpressionInterface $subExpression)
    {
        if ($this->finalized)
        {
            throw new OxtExceptionInternal(
                'Attempt to push a node to a finalized subexpression list',
                OxtExceptionInternal::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->items[] = $subExpression;
        $this->index = 0;
        ++$this->count;
        
    }
    
    public function finalize()
    {
        usort ($this->items, __CLASS__ . '::compareStartIndex');
        $this->finalized = true;
    }
    
    public function isEmpty()
    {
        return ! ($this->count > 0);
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
    
    // protected //
    
    protected static function compareStartIndex (OxtSubExpressionInterface $a, OxtSubExpressionInterface $b)
    {
        return $a->getStartIndex() - $b->getStartIndex();
    }
}
