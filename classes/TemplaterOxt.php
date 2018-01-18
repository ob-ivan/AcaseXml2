<?php

require_once MODULE_DIRECTORY . '/OxtProcessor/OxtProcessor.class.php';

class TemplaterOxt extends TemplaterAbstract implements TemplaterInterface
{
    // fields //
    
    protected $fileCacheEnabled  = false;
    protected $processorCacheEnabled = true;
    protected $processorCache = array();
    
    // TemplaterInterface //
    
    public function apply ($RequestName, $xmlString)
    {
        $oxtPath = $this->templatesDirectory . '/' . $RequestName . '.oxt';
        if (! file_exists ($oxtPath))
        {
            throw new Exception ('Не найден шаблон в стиле "' . $this->styleName . '" для запроса "' . $RequestName . '"');
        }
        
        $xml = new DOMDocument();
        $xml->loadXML ($xmlString);
        
        $oxt = $this->getProcessor ($oxtPath);
        return $oxt->applyTemplates ($xml);
    }
    
    // extension //
    
    public function enableFileCache ($enable = true)
    {
        $this->fileCacheEnabled = !! $enable;
    }
    
    // protected //
    
    protected function getProcessor ($templatePath)
    {
        if ($this->processorCacheEnabled && isset($this->processorCache[$templatePath]))
        {
            return $this->processorCache[$templatePath];
        }
        
        $oxt = new OxtProcessor($this->fileCacheEnabled);
        $oxt->loadTemplate ($templatePath);
        
        if ($this->processorCacheEnabled)
        {
            $this->processorCache[$templatePath] = $oxt;
        }
        
        return $oxt;
    }
}
