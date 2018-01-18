<?php

interface OxtFreeZoneInterface
{
    public function __construct ($startIndex, $endIndex);
    
    public function getStartIndex();
    
    public function getEndIndex();
}

