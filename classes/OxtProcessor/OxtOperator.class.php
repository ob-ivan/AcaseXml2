<?php

require_once dirname(__FILE__) . '/OxtOperator.interface.php';
require_once dirname(__FILE__) . '/OxtOperatorAssociativity.type.php';

class OxtOperator implements OxtOperatorInterface
{
    // fields //
    
    protected $symbol;
    protected $priority;
    protected $associativity;
    protected $type;
    
    // OxtOperatorInterface //
    
    public function __construct (
                            $symbol, 
                            $priority, 
        OxtAssociativity    $associativity, 
        OxtOperatorType     $type
    ) {
        $this->symbol           = strval ($symbol);
        $this->priority         = intval ($priority);
        $this->associativity    = $associativity;
        $this->type             = $type;
    }
    
    public function getSymbol()
    {
        return $this->symbol;
    }
    
    public function getPriority()
    {
        return $this->priority;
    }
    
    public function getAssociativity()
    {
        return $this->associativity;
    }
    
    public function getType()
    {
        return $this->type;
    }
}

