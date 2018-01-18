<?php

require_once dirname(__FILE__) . '/OxtSubExpression.interface.php';
require_once dirname(__FILE__) . '/OxtFreeZone.interface.php';

interface OxtExpressionInterface
{
    public function __construct();
    
    // create
    
    public function pushText ($text);
    
    public function pushSubExpression (OxtSubExpressionInterface $subExpression);
    
    public function pushFreeZone (OxtFreeZoneInterface $freeZone);
    
    // finalize
    
    public function finalize();
    
    // read
    
    public function isEmpty();
    
    /**
     * @return string
    **/
    public function getText();
    
    public function getTextLength();
    
    public function getSubstr($start, $end);
    
    /**
     * @return OxtSubExpressionList
    **/
    public function getSubExpressionList();
    
    /**
     * @return OxtFreeZoneList
    **/
    public function getFreeZoneList();
    
    /**
     * Вырезает кусок выражения от индекса начала до индекса конца.
     *
     * Если указан пустой кусок, возвращает false.
     *
     * @param   int             $start
     * @param   int             $end    Если указано отрицательное значение, оно отсчитывается с конца выражения.
     * @return  OxtExpression | false
    **/
    public function slice ($start, $end);
    
    /**
     * Разбивает выражение по указанному разделителю и возвращает массив подвыражений.
     *
     * @return array(OxtExpression)
    **/
    public function split ($separator = ',');
}

