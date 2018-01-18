<?php

require_once dirname(__FILE__) . '/OxtExpressionValue.interface.php';
require_once dirname(__FILE__) . '/OxtExpressionValueNamedList.interface.php';

class OxtExpressionValueNamedList implements OxtExpressionValueNamedListInterface
{
    // fields //
    
    protected $items = array(); // ключ => значение.
    protected $keys = array(); // индекс => ключ.
    protected $index = 0;
    protected $count = 0;
    protected $finalized = false;
    
    // Iterator //
    
    public function current()
    {
        return $this->items[$this->keys[$this->index]];
    }
    
    public function key()
    {
        return $this->keys[$this->index];
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
    
    // OxtExpressionValueNamedListInterface //
    
    public function __constructor()
    {
    }
    
    public function set ($itemName, OxtExpressionValueInterface $itemValue)
    {
        if ($this->finalized)
        {
            throw new OxtProcessorInternalException(
                'Attempt to set a named item in a finalized expression value list',
                OxtProcessorInternalException::CODE_PUSH_TO_FINALIZED
            );
        }
        if (! isset($this->items[$itemName]))
        {
            $this->keys[] = $itemName;
            $this->index = 0;
            ++$this->count;
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
    
    public function exists ($itemName)
    {
        return isset ($this->items[$itemName]);
    }
}
