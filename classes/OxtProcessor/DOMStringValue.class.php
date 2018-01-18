<?php

class DOMStringValue
{
    public static function get (DOMNode $node)
    {
        switch ($node->nodeType)
        {
            case XML_ELEMENT_NODE :
            {
                $return = '';
                foreach ($node->childNodes as $child)
                {
                    $return .= self::nodeStringValue($child);
                }
                return $return;
            }
            case XML_ATTRIBUTE_NODE :
            {
                return $node->value;
            }
            case XML_TEXT_NODE :
            {
                return $node->wholeText;
            }
        }
        throw new OxtExceptionCompile(
            'Unknown domnode type: ' . $node->nodeType,
            OxtExceptionCompile::CODE_UNKNOWN_NODE_TYPE
        );
    }
}
