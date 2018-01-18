<?php

require_once dirname(__FILE__) . '/OxtOperator.interface.php';

interface OxtOperatorListInterface extends Iterator
{
    public function __construct();
    
    public function push (OxtOperatorInterface $operator);
}
