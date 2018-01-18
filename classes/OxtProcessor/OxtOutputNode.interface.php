<?php

require_once dirname(__FILE__) . '/OxtOutputBlock.interface.php';
require_once dirname(__FILE__) . '/OxtOutputNode.type.php';

interface OxtOutputNodeInterface
{
    public function __construct(
        OxtOutputNodeType       $type,
                                $text, // string
        OxtOutputBlockInterface $classes    = null,
        OxtOutputBlockInterface $attributes = null,
        OxtOutputBlockInterface $styles     = null,
        OxtOutputBlockInterface $children   = null
    );
    
    public function toString();
}
