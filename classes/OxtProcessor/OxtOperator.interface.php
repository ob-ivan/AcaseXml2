<?php

require_once dirname(__FILE__) . '/OxtOperatorAssociativity.type.php';

interface OxtOperatorInterface
{
    public function __construct (
                            $symbol, 
                            $priority, 
        OxtAssociativity    $associativity, 
        OxtOperatorType     $type
    );
    
    public function getSymbol();
    
    public function getPriority();
    
    public function getAssociativity();
    
    public function getType();
}

