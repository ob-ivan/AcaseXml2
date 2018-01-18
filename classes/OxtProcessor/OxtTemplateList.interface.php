<?php

require_once dirname(__FILE__) . '/OxtCodeNode.interface.php';

interface OxtTemplateListInterface
{
    public function __construct();
    
    public function set ($templateName, OxtCodeNodeInterface $code);
    
    /**
     * return finalized clone of self.
    **/
    public function finalization();
    
    public function finalize();
    
    public function get ($templateName);
    
    public function exists ($templateName);
}
