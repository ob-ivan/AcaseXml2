<?php

require_once dirname(__FILE__) . '/OxtExpressionValue.interface.php';

interface OxtExpressionValueNamedListInterface extends Iterator
{
    public function __constructor();
    
    /**
     * Добавить именованное значение.
    **/
    public function set ($itemName, OxtExpressionValueInterface $itemValue);
    
    public function finalize();
    
    public function get ($itemName);
    
    // Отвечает на вопрос, существует ли ключ.
    public function exists ($itemName);
}
