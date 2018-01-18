<?php

interface OxtParenthesesStackInterface
{
    public function __construct();
    
    public function push ($openCharacter, $startIndex);
    
    public function getTopCharacter();
    
    public function popStartIndex();
    
    public function isEmpty();
}
