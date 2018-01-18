<?php

require_once dirname(__FILE__) . '/OxtEvaluationContext.interface.php';

class OxtEvaluationContext implements OxtEvaluationContextInterface
{
    // fields //
    
    protected $document;
    protected $contextNode;
    protected $contextPosition;
    protected $contextSize;
    protected $currentNode; // Значение функции current(). Совпадает с contextNode, кроме как в предикатах сложных выборок.
    protected $arguments;
    protected $constants;
    protected $variables;
    protected $templates;
    
    // OxtEvaluationContextInterface //
    
    public function __construct (
        DOMDocument                             $document,
        DOMElementOrRootInterface               $contextNode,
                                                $contextPosition,
                                                $contextSize,
        DOMElementOrRootInterface               $currentNode,
        OxtExpressionValueNamedListInterface    $arguments,
        OxtExpressionValueNamedListInterface    $constants,
        OxtExpressionValueNamedListInterface    $variables,
        OxtTemplateListInterface                $templates
    ) {
        $this->document         = $document;
        $this->contextNode      = $contextNode;
        $this->contextPosition  = $contextPosition;
        $this->contextSize      = $contextSize;
        $this->currentNode      = $currentNode;
        $this->arguments        = $arguments;
        $this->constants        = $constants;
        $this->variables        = $variables;
        $this->templates        = $templates;
    }
    
    public function getDocument()
    {
        return $this->document;
    }
    
    public function getContextNode()
    {
        return $this->contextNode;
    }
    
    public function getContextPosition()
    {
        return $this->contextPosition;
    }
    
    public function getContextSize()
    {
        return $this->contextSize;
    }
    
    public function getCurrentNode()
    {
        return $this->currentNode;
    }
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function getConstants()
    {
        return $this->constants;
    }
    
    public function getVariables()
    {
        return $this->variables;
    }
    
    public function getTemplates()
    {
        return $this->templates;
    }
}

