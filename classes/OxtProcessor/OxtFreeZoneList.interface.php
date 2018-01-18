<?php

require_once dirname(__FILE__) . '/OxtFreeZone.interface.php';

interface OxtFreeZoneListInterface extends Iterator
{
    public function __construct();
    
    public function push (OxtFreeZoneInterface $freeZone);
    
    public function finalize();
    
    public function finalization();
    
    public function getCount();
}
