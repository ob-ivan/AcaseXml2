<?php

require_once dirname(__FILE__) . '/OxtCodeBlock.interface.php';

interface OxtCompileInterface
{
    /**
     *  @return string
    **/
    public static function compile (OxtCodeBlockInterface $code, DOMDocument $data);
}
