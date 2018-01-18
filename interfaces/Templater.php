<?php

interface TemplaterInterface
{
    public function __construct ($styleName);
    
    public function apply ($RequestName, $xmlString);
}
