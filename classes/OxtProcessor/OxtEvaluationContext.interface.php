<?php

interface OxtEvaluationContextInterface
{
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
    );
    
    public function getDocument();
    
    public function getContextNode();
    
    public function getContextPosition();
    
    public function getContextSize();
    
    public function getCurrentNode();
    
    public function getArguments();
    
    public function getConstants();
    
    public function getVariables();
    
    public function getTemplates();
}

