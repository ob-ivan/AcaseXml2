<?php

require_once dirname(__FILE__) . '/OxtCodeNode.interface.php';

class OxtCodeNode implements OxtCodeNodeInterface
{
    // fields //
    
    protected $type;
    protected $text;
    protected $select;
    protected $childNodes;
    protected $argumentNodes;
    protected $priority;
    protected $axis;
    
    // OxtCodeNodeInterface //
    
    public function __construct(
        OxtCodeNodeType         $type,
                                $text           = null,
        OxtCodeNodeInterface    $select         = null,
        OxtCodeBlockInterface   $childNodes     = null,
        OxtCodeBlockInterface   $argumentNodes  = null,
                                $priority       = 0,
        OxtAxisType             $axis           = null
    ) {
        $this->type = $type;
        
        if (! empty ($text))
        {
            $this->text = $text;
        }
        if (! empty ($select))
        {
            $this->select = $select;
        }
        $this->childNodes = $childNodes;
        $this->argumentNodes = $argumentNodes;
        if (! is_null($priority))
        {
            $this->priority = intval($priority);
        }
        if (! is_null($axis))
        {
            $this->axis = $axis;
        }
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getText()
    {
        return $this->text;
    }
    
    public function getSelect()
    {
        return $this->select;
    }
    
    public function getChildNodes()
    {
        if (! $this->childNodes)
        {
            $this->childNodes = new OxtCodeBlock();
            $this->childNodes->finalize();
        }
        return $this->childNodes;
    }
    
    public function hasChildNodes()
    {
        if (! $this->childNodes)
        {
            $this->childNodes = new OxtCodeBlock();
            $this->childNodes->finalize();
        }
        return ! $this->childNodes->isEmpty();
    }
    
    public function getArgumentNodes()
    {
        if (! $this->argumentNodes)
        {
            $this->argumentNodes = new OxtCodeBlock();
            $this->argumentNodes->finalize();
        }
        return $this->argumentNodes;
    }
    
    public function hasArgumentNodes()
    {
        if (! $this->argumentNodes)
        {
            $this->argumentNodes = new OxtCodeBlock();
            $this->argumentNodes->finalize();
        }
        return ! $this->argumentNodes->isEmpty();
    }
    
    public function getPriority()
    {
        return $this->priority;
    }
    
    public function getAxis()
    {
        return $this->axis;
    }

    public function printTree()
    {
        return $this->printTree_recursive();
    }
    
    // protected //
    
    protected function printTree_recursive ($depth = 0)
    {
        $indent = str_repeat('|' . str_repeat(' ', 15), $depth);
        $return = get_class($this->type);
        if ($this->text)
        {
            $return .= ' "';
            if ($this->axis)
            {
                $axis = $this->axis;
                $return .= $axis::SPECIFIER . '::';
            }
            $return .= $this->text . '"';
        }
        $return .= '<br>';
        if ($this->select)
        {
            $return .= $indent . 'select=         ' . $this->select->printTree_recursive($depth + 1) . '<br>';
        }
        if ($this->argumentNodes)
        {
            $index = 0;
            foreach ($this->argumentNodes as $argumentNode)
            {
                if (! $index)
                {
                    $return .= 
                        $indent . 
                        'arguments[' . $this->argumentNodes->getCount() . ']=0. ' . 
                        $argumentNode->printTree_recursive($depth + 1);
                }
                else
                {
                    $return .= $indent . '|            ' . $index . '. ' . $argumentNode->printTree_recursive($depth + 1);
                }
                ++$index;
            }
        }
        if ($this->childNodes)
        {
            $index = 0;
            foreach ($this->childNodes as $childNode)
            {
                if (! $index)
                {
                    $return .= 
                        $indent . 
                        'children[' . $this->childNodes->getCount() . ']= 0. ' .
                        $childNode->printTree_recursive($depth + 1);
                }
                else
                {
                    $return .= $indent . '|            ' . $index . '. ' . $childNode->printTree_recursive($depth + 1);
                }
                ++$index;
            }
        }
        return $return;
    }
}
