<?php

require_once dirname(__FILE__) . '/OxtParenthesesStack.interface.php';

class OxtParenthesesStack implements OxtParenthesesStackInterface
{
    // const //
    
    const KEY_CHARACTER     = __LINE__;
    const KEY_START_INDEX   = __LINE__;
    
    // fields //
    
    protected $pairs = array();
    
    // OxtParenthesesStackInterface //
    
    public function __construct()
    {
    }
    
    public function push ($openCharacter, $startIndex)
    {
        $openCharacter = strval ($openCharacter);
        array_unshift ($this->pairs, array (
            self::KEY_CHARACTER     => $openCharacter, 
            self::KEY_START_INDEX   => intval ($startIndex),
        ));
    }
    
    public function getTopCharacter()
    {
        if (empty ($this->pairs))
        {
            return '';
        }
        return $this->pairs[0][self::KEY_CHARACTER];
    }
    
    public function popStartIndex()
    {
        $topPair = array_shift($this->pairs);
        return $topPair[self::KEY_START_INDEX];
    }
    
    public function isEmpty()
    {
        return empty ($this->pairs);
    }
}
