<?php

require_once dirname(__FILE__) . '/OxtExpressionValue.type.php';

interface OxtExpressionValueInterface
{
    public function __construct(
        OxtExpressionValueType $type,
        $primitiveValue
    );
    
    public function getType();
    
    public function getValue();
    
    /**
     * xpath string()
     *
     * @return OxtExpressionValue
    **/
    public function toString();
    
    /**
     * xpath boolean()
     *
     * @return OxtExpressionValue
    **/
    public function toBoolean();
    
    /**
     * xpath number()
     *
     * @return OxtExpressionValue
    **/
    public function toNumber();
}

