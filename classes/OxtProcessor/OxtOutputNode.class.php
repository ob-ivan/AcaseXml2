<?php

require_once dirname(__FILE__) . '/OxtOutputNode.interface.php';

class OxtOutputNode implements OxtOutputNodeInterface
{
    // fields //
    
    protected $type;
    protected $text;
    protected $classes;
    protected $attributes;
    protected $styles;
    protected $children;
    
    // OxtOutputNodeInterface //
    
    public function __construct(
        OxtOutputNodeType       $type,
                                $text, // string
        OxtOutputBlockInterface $classes    = null,
        OxtOutputBlockInterface $attributes = null,
        OxtOutputBlockInterface $styles     = null,
        OxtOutputBlockInterface $children   = null
    ) {
        $this->type = $type;
        $this->text = strval ($text);
        if (! empty($classes))
        {
            $this->classes = $classes;
        }
        if (! empty($attributes))
        {
            $this->attributes = $attributes;
        }
        if (! empty($styles))
        {
            $this->styles = $styles;
        }
        if (! empty($children))
        {
            $this->children = $children;
        }
    }
    
    /**
     * return string
    **/
    public function toString()
    {
        // literal
        if ($this->type instanceof OxtOutputNodeTypeString ||
            $this->type instanceof OxtOutputNodeTypeNumber ||
            $this->type instanceof OxtOutputNodeTypeBoolean ||
            $this->type instanceof OxtOutputNodeTypeClass
        ) {
            return $this->text;
        }
        
        // html tag
        if ($this->type instanceof OxtOutputNodeTypeTag)
        {
            $return = '<' . $this->text;
            
            // classes
            if ($this->classes && ! $this->classes->isEmpty())
            {
                $classes = array();
                foreach ($this->classes as $class)
                {
                    $classes[] = $class->toString();
                }
                if (! empty ($classes))
                {
                    $return .= ' class="' . self::escapeAttribute(implode(' ', $classes)) . '"';
                }
            }
            
            // generic attributes
            if ($this->attributes && ! $this->attributes->isEmpty())
            {
                foreach ($this->attributes as $attribute)
                {
                    $return .= ' ' . $attribute->toString();
                }
            }
            
            // styles
            if ($this->styles && ! $this->styles->isEmpty())
            {
                $styles = array();
                foreach ($this->styles as $style)
                {
                    $styles[] = $style->toString();
                }
                if (! empty ($styles))
                {
                    $return .= ' style="' . implode(';', $styles) . '"';
                }
            }
            
            
            $return .= '>';
            $return .= '</' . $this->text . '>';
            return $return;
        }
        
        // attribute
        if ($this->type instanceof OxtOutputNodeTypeAttribute)
        {
            $return = $this->text;
            if ($this->children && ! $this->children->isEmpty())
            {
                $return .= '="';
                foreach ($this->children as $child)
                {
                    $return .= self::escapeAttribute($child->toString());
                }
                $return .= '"';
            }
            return $return;
        }
        
        // style
        if ($this->type instanceof OxtOutputNodeTypeStyle)
        {
            $return = $this->text;
            if ($this->children && ! $this->children->isEmpty())
            {
                $return .= ':';
                foreach ($this->children as $child)
                {
                    $return .= self::escapeAttribute($child->toString());
                }
            }
            return $return;
        }
    }
    
    // protected //
    
    protected static function escapeAttribute($content)
    {
        return htmlentities($content);
    }
}

