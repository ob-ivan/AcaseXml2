<?php

interface OxtSubExpressionInterface
{
    public function __construct ($openCharacter, $startIndex, $endIndex);
    
    public function getOpenCharacter();
    
    public function getStartIndex();
    
    public function getEndIndex();
}
