<?php

require_once dirname(__FILE__) . '/OxtCodeBlock.interface.php';
require_once dirname(__FILE__) . '/OxtCodeNode.type.php';

interface OxtCodeNodeInterface
{
    public function __construct(
        OxtCodeNodeType         $type,
                                $text           = null, // string
        OxtCodeNodeInterface    $select         = null,
        OxtCodeBlockInterface   $childNodes     = null,
        OxtCodeBlockInterface   $argumentNodes  = null,
                                $priority       = null, // только для Operator
        OxtAxisType             $axis           = null  // только для NameTest и NodeTest
    );
    
    public function getType();
    
    public function getText();
    
    public function getSelect();
    
    public function hasChildNodes();
    
    public function getChildNodes();
    
    public function hasArgumentNodes();
    
    public function getArgumentNodes();
    
    public function getPriority();
    
    public function getAxis();
    
    /**
     * @return string
    **/
    public function printTree();
}
