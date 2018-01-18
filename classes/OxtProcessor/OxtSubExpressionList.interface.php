<?php

require_once dirname(__FILE__) . '/OxtSubExpression.interface.php';

interface OxtSubExpressionListInterface extends Iterator
{
    public function __construct();
    
    public function push (OxtSubExpressionInterface $subExpression);
    
    public function finalize();
    
    public function isEmpty();
    
    public function finalization();
}

