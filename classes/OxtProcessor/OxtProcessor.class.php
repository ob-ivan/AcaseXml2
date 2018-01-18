<?php

require_once dirname(__FILE__) . '/OxtProcessor.interface.php';
require_once dirname(__FILE__) . '/OxtException.class.php';
require_once dirname(__FILE__) . '/OxtCodeBlock.class.php';
require_once dirname(__FILE__) . '/OxtParse.class.php';
require_once dirname(__FILE__) . '/OxtCompile.class.php';

class OxtProcessor implements OxtProcessorInterface
{
    // variables //
    
    protected $code; // instanceof OxtCodeBlock
    protected $enableCache = true;
    
    // OxtProcessorInterface //
    
    public function __construct ($enableCache = true) 
    {
        $this->enableCache = !! $enableCache;
        $this->code = new OxtCodeBlock();
    }
    
    public function loadTemplate ($filepath)
    {
        $restored = false;
        $key = 'OxtProcessor_cachedTemplate:' . $filepath;
        if ($this->enableCache && Cache::exists($key))
        {
            $fileCode = Cache::get($key);
            if ($fileCode instanceof OxtCodeBlock)
            {
                $restored = true;
            }
            unset ($serialized);
        }
        if (! $restored)
        {
            $fileContent = file_get_contents($filepath);
            $fileCode = OxtParse::parseInput ($fileContent);
            if ($this->enableCache)
            {
                Cache::set($key, $fileCode);
            }
        }
        unset ($restored, $hash);
        
        foreach ($fileCode as $node)
        {
            $this->code->push ($node);
        }
    }
    
    public function applyTemplates (DOMDocument $xml)
    {
        return OxtCompile::compile($this->code->finalization(), $xml);
    }
}
