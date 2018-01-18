<?php

require_once dirname(__FILE__) . '/OxtCodeNode.interface.php';
require_once dirname(__FILE__) . '/OxtTemplateList.interface.php';

class OxtTemplateList implements OxtTemplateListInterface
{
    // fields //
    
    protected $templates = array();
    protected $finalized = false;
    
    // OxtTemplateListInterface //
    
    public function __construct()
    {
    }
    
    public function set ($templateName, OxtCodeNodeInterface $code)
    {
        if ($this->finalized)
        {
            throw new OxtProcessorInternalException(
                'Attempt to push a template to a finalized list',
                OxtProcessorInternalException::CODE_PUSH_TO_FINALIZED
            );
        }
        $this->templates[$templateName] = $code;
    }
    
    public function finalization()
    {
        $clone = new self();
        foreach ($this->templates as $templateName => $code)
        {
            $clone->set($templateName, $code);
        }
        $clone->finalize();
        return $clone;
    }
    
    public function finalize()
    {
        $this->finalized = true;
    }
    
    public function get ($templateName)
    {
        return $this->templates[$templateName];
    }
    
    public function exists ($templateName)
    {
        return isset ($this->templates[$templateName]);
    }
}
