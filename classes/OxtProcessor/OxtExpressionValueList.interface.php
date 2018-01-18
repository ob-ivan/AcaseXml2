<?php

require_once dirname(__FILE__) . '/OxtExpressionValue.interface.php';

interface OxtExpressionValueListInterface extends Iterator
{
    public function __construct();
    
    public function push (OxtExpressionValueInterface $expressionValue);
    
    public function finalize();
    
    public function getCount();
    
    public function getItem ($index);
}

