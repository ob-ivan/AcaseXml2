<?php

require_once dirname(__FILE__) . '/OxtFreeZone.interface.php';

class OxtFreeZone implements OxtFreeZoneInterface
{
    // fields //
    
    protected $startIndex;
    protected $endIndex;
    
    // OxtFreeZoneInterface //
    
    public function __construct ($startIndex, $endIndex)
    {
        $this->startIndex   = intval ($startIndex);
        $this->endIndex     = intval ($endIndex);
    }
    
    public function getStartIndex()
    {
        return $this->startIndex;
    }
    
    public function getEndIndex()
    {
        return $this->endIndex;
    }
}

