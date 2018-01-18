<?php

require_once dirname(__FILE__) . '/OxtCodeNode.interface.php';

interface OxtCodeBlockInterface extends Iterator
{
    // Кусок кода создаётся пустым и открытым для новых чайлд-нодов.
    public function __construct();
    
    // Чайлд-ноды можно добавлять, пока код открыт.
    public function push (OxtCodeNodeInterface $node);
    
    // Когда всё добавлено, надо закрыть вход.
    public function finalize();
    
    // Отдать закрытого клона, а за собой оставить право расширяться.
    public function finalization();
    
    public function isEmpty();
    
    public function getCount();
    
    /**
     * Получить элемент по индексу, счёт от 0.
     *
     *  @param  integer                 $index
     *  @return OxtCodeNodeInterface
    **/
    public function getItem ($index);
}
