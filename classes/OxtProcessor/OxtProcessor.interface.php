<?php

interface OxtProcessorInterface
{
    public function __construct();
    
    public function loadTemplate ($filepath);
    
    public function applyTemplates (DOMDocument $xml);
}

