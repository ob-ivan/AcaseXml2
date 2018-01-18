<?php

require_once dirname(__FILE__) . '/OxtExpressionValue.interface.php';
require_once dirname(__FILE__) . '/DOMStringValue.class.php';

class OxtExpressionValue implements OxtExpressionValueInterface
{
    // fields //
    
    protected $type;
    protected $value;
    
    // OxtExpressionValueInterface //
    
    public function __construct(
        OxtExpressionValueType $type,
        $primitiveValue
    ) {
        $this->type = $type;
        
        // Привести примитивное значение к типу, если это возможно.
        if ($type instanceof OxtExpressionValueTypeNodeset)
        {
            if (! is_array($primitiveValue))
            {
                throw new OxtExceptionCompile(
                    'Cannot convert value to a node set',
                    OxtExceptionCompile::CODE_ILLEGAL_TYPE_CONVERSION
                );
            }
            $newValue = array();
            foreach ($primitiveValue as $node)
            {
                if (! $node instanceof DOMNode)
                {
                    throw new OxtExceptionCompile(
                        'Node set must only contain nodes, ' . get_class($node) . ' given',
                        OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
                    );
                }
                $newValue[] = $node;
            }
            $this->value = $newValue;
        }
        elseif ($type instanceof OxtExpressionValueTypeBoolean)
        {
            $this->value = !! $primitiveValue;
        }
        elseif ($type instanceof OxtExpressionValueTypeNumber)
        {
            $this->value = floatval($primitiveValue);
        }
        elseif ($type instanceof OxtExpressionValueTypeString)
        {
            if (is_array($primitiveValue))
            {
                $this->value = self::nodesetToString($primitiveValue);
            }
            else
            {
                $this->value = strval($primitiveValue);
            }
        }
        elseif ($type instanceof OxtExpressionValueTypeOutput)
        {
            if (! $primitiveValue instanceof OxtOutputBlockInterface)
            {
                throw new OxtExceptionCompile(
                    'Primitive value of output type must be an instance of OutputBlock class',
                    OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
                );
            }
            $primitiveValue->finalize();
            $this->value = $primitiveValue;
        }
        else
        {
            throw new OxtExceptionCompile(
                'Unknown expression value type: ' . get_class($type),
                OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
            );
        }
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * xpath string()
     *
     * @return OxtExpressionValue
    **/
    public function toString()
    {
        if ($this->type instanceof OxtExpressionValueTypeString)
        {
            return $this;
        }
        if ($this->type instanceof OxtExpressionValueTypeNodeset)
        {
            /**
             * A node-set is converted to a string by returning the string-value
             * of the node in the node-set that is first in document order.
             * If the node-set is empty, an empty string is returned.
            **/
            // TODO: определить, который нод первый по порядку.
            
            if (empty ($this->value))
            {
                return new OxtExpressionValue (new OxtExpressionValueTypeString, '');
            }
            foreach ($this->value as $node)
            {
                break;
            }
            return new OxtExpressionValue (
                new OxtExpressionValueTypeString, 
                DOMStringValue::get($node)
            );
        }
        if ($this->type instanceof OxtExpressionValueTypeBoolean)
        {
            if ($this->value)
            {
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeString, 
                    'true'
                );
            }
            return new OxtExpressionValue (
                new OxtExpressionValueTypeString, 
                'false'
            );
        }
        if ($this->type instanceof OxtExpressionValueTypeNumber)
        {
            // TODO: реализовать по стандарту.
            return new OxtExpressionValue (
                new OxtExpressionValueTypeString, 
                strval($this->value)
            );
        }
        if ($this->type instanceof OxtExpressionValueTypeOutput)
        {
            return new OxtExpressionValue (
                new OxtExpressionValueTypeString, 
                $this->value->toString()
            );
        }
        throw new OxtExceptionCompile(
            'Could not convert expression value of type ' . get_class($this->type) . ' to string',
            OxtExceptionCompile::CODE_ILLEGAL_TYPE_CONVERSION
        );
    }
    
    /**
     * xpath boolean()
     *
     * @return OxtExpressionValue
    **/
    public function toBoolean()
    {
        if ($this->type instanceof OxtExpressionValueTypeBoolean)
        {
            return $this;
        }
        
        $trueValue  = new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), true);
        $falseValue = new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), false);
        
        if ($this->type instanceof OxtExpressionValueTypeNumber)
        {
            if ($this->value == 'NaN' || $this->value == '+0' || $this->value == '-0' || $this->value == 0)
            {
                return $falseValue;
            }
            return $trueValue;
        }
        if ($this->type instanceof OxtExpressionValueTypeNodeset ||
            $this->type instanceof OxtExpressionValueTypeString
        ) {
            if (empty ($this->value))
            {
                return $falseValue;
            }
            return $trueValue;
        }
        if ($this->type instanceof OxtExpressionValueTypeOutput)
        {
            $string = $this->value->toString();
            if (empty ($string))
            {
                return $falseValue;
            }
            return $trueValue;
        }
        throw new OxtExceptionCompile(
            'Could not convert expression value of type ' . get_class($this->type) . ' to boolean',
            OxtExceptionCompile::CODE_ILLEGAL_TYPE_CONVERSION
        );
    }

    /**
     * xpath number()
     *
     * @return OxtExpressionValue
    **/
    public function toNumber()
    {
        if ($this->type instanceof OxtExpressionValueTypeNumber)
        {
            return $this;
        }
        
        if ($this->type instanceof OxtExpressionValueTypeBoolean)
        {
            /**
             * Boolean true is converted to 1; boolean false is converted to 0.
            **/
            return new OxtExpressionValue (
                new OxtExpressionValueTypeNumber(), 
                intval ($this->value)
            );
        }
        
        $string = '';
        $found = false;
        
        if ($this->type instanceof OxtExpressionValueTypeString)
        {
            $string = $this->value;
            $found = true;
        }
        elseif ($this->type instanceof OxtExpressionValueTypeOutput)
        {
            $string = $this->value->toString();
            $found = true;
        }
        elseif ($this->type instanceof OxtExpressionValueTypeNodeset)
        {
            /**
             * A node-set is first converted to a string as if by a call to the string function 
             * and then converted in the same way as a string this.
            **/
            $string = $this->toString()->getValue();
            $found = true;
        }
        
        if ($found)
        {
            /**
             * A string that consists of optional whitespace followed by an optional minus sign 
             * followed by a Number followed by whitespace is converted to the IEEE 754 number
             * that is nearest (according to the IEEE 754 round-to-nearest rule) to the mathematical value 
             * represented by the string; any other string is converted to NaN.
            **/
            // TODO: реализовать вышеуказанный алгоритм.
            $number = intval (trim ($string));
            return new OxtExpressionValue(
                new OxtExpressionValueTypeNumber(),
                $number
            );
        }
        
        throw new OxtExceptionCompile(
            'Could not convert expression value of type ' . get_class($this->type) . ' to number',
            OxtExceptionCompile::CODE_ILLEGAL_TYPE_CONVERSION
        );
    }
}

