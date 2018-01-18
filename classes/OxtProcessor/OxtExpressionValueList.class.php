<?php

require_once dirname(__FILE__) . '/OxtExpressionValueList.interface.php';

class OxtExpressionValueList implements OxtExpressionValueListInterface
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
    
    // OxtExpressionValueListInterface //
    
    public function __construct()
    {
    }
    
    public function push (OxtExpressionValueInterface $expressionValue)
    {
        if ($this->finalized)
        {
            throw new OxtExceptionInternal(
                'Attempt to push an expression value to a finalized list',
                OxtExceptionInternal::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->items[] = $expressionValue;
        $this->index = 0;
        ++$this->count;
    }
    
    public function finalize()
    {
        $this->finalized = true;
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
                'Undefined expression value list offset: ' . $index . '; count = ' . $this->count . ' (apriori)',
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
            'Undefined expression value list offset: ' . $index . '; count = ' . $this->count . ' (aposteriori)',
            OxtProcessorInternalException::CODE_UNDEFINED_OFFSET
        );
    }
}

