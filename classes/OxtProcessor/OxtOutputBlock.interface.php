<?php

require_once dirname(__FILE__) . '/OxtOutputNode.interface.php';

interface OxtOutputBlockInterface extends Iterator
{
    public function __construct();
    
    public function push(OxtOutputNodeInterface $node);
    
    public function finalize();
    
    public function isEmpty();
    
    public function toString();
}

