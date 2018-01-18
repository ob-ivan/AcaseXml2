<?php

require_once dirname(__FILE__) . '/OxtSubExpression.interface.php';

class OxtSubExpression implements OxtSubExpressionInterface
{
    // fields //
    
    protected $openCharacter;
    protected $startIndex;
    protected $endIndex;
    
    // OxtSubExpressionInterface //
    
    public function __construct ($openCharacter, $startIndex, $endIndex)
    {
        $openCharacter = strval ($openCharacter);
        
        $this->openCharacter    = $openCharacter[0];
        $this->startIndex       = intval($startIndex);
        $this->endIndex         = intval($endIndex);
    }
    
    public function getOpenCharacter()
    {
        return $this->openCharacter;
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
