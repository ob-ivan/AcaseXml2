<?php

require_once dirname(__FILE__) . '/OxtCompile.interface.php';
require_once dirname(__FILE__) . '/OxtCodeBlock.interface.php';

require_once dirname(__FILE__) . '/DOMElementOrRoot.class.php';
require_once dirname(__FILE__) . '/DOMStringValue.class.php';

require_once dirname(__FILE__) . '/OxtTemplateList.class.php';
require_once dirname(__FILE__) . '/OxtOutputNode.class.php';
require_once dirname(__FILE__) . '/OxtOutputBlock.class.php';
require_once dirname(__FILE__) . '/OxtOutputNamedList.class.php';
require_once dirname(__FILE__) . '/OxtExpressionValue.class.php';
require_once dirname(__FILE__) . '/OxtExpressionValueList.class.php';
require_once dirname(__FILE__) . '/OxtExpressionValueNamedList.class.php';
require_once dirname(__FILE__) . '/OxtEvaluationContext.class.php';

class OxtCompile implements OxtCompileInterface
{
    // OxtInterface //

    // TODO: передавать аргументы для main'а.
    public static function compile (OxtCodeBlockInterface $code, DOMDocument $document)
    {
        $constants = new OxtExpressionValueNamedList();
        $templates = new OxtTemplateList();
        $main = false;

        foreach ($code as $node)
        {
            // Переменные, объявленные на верхнем уровне, -- это глобальные константы.
            if ($node->getType() instanceof OxtCodeNodeTypeVariable)
            {
                $variables = new OxtExpressionValueNamedList();
                $arguments = new OxtExpressionValueNamedList();
                $arguments->finalize();
                $root = new DOMElementOrRoot();
                $topContext = new OxtEvaluationContext (
                    $document,
                    $root,  // context node
                    1,      // context position
                    1,      // context size
                    $root,  // current node
                    $arguments,
                    $constants,
                    $variables,
                    $templates->finalization()
                );
                $childNodes = $node->getChildNodes();
                $value = self::calculateVariableValue($childNodes, $topContext);
                $constants->set($node->getText(), $value);
                unset ($childNodes, $variables, $arguments, $topContext, $done, $root);
            }
            elseif ($node->getType() instanceof OxtCodeNodeTypeTemplate)
            {
                if ($templates->exists($node->getText()))
                {
                    throw new OxtExceptionCompile(
                        'Duplicate declaration of template "' . $node->getText() . '"',
                        OxtExceptionCompile::CODE_DUPLICATE_TEMPLATE_NAME
                    );
                }
                $templates->set($node->getText(), $node);
            }
        }
        unset ($node);
        $constants->finalize();
        $templates->finalize();

        if (! $templates->exists('main'))
        {
            throw new OxtExceptionCompile(
                'Template "main" in absent',
                OxtExceptionCompile::CODE_ABSENT_MAIN
            );
        }

        $variables = new OxtExpressionValueNamedList();
        $arguments = new OxtExpressionValueNamedList();
        $arguments->finalize();
        $root = new DOMElementOrRoot();
        return self::compileNode (
            $templates->get('main'),
            new OxtEvaluationContext(
                $document,
                $root,  // context node
                1,      // context position
                1,      // context size
                $root,  // current node
                $arguments,
                $constants,
                $variables,
                $templates
            )
        )->toString();
    }

    // protected //

    /**
     *  @return OxtOutputBlockInterface
    **/
    protected static function compileNode(
        OxtCodeNodeInterface            $node,
        OxtEvaluationContextInterface   $context
    ) {
        $return = new OxtOutputBlock();

        $nodeType = $node->getType();

        // &template() definition
        if ($nodeType instanceof OxtCodeNodeTypeTemplate)
        {
            // Скрестить переданные при вызове аргументы ($context->getArguments()) с дефолтными значениями ($node->getArgumentNodes()).
            $newArguments = new OxtExpressionValueNamedList();
            foreach ($node->getArgumentNodes() as $argumentDefault)
            {
                $found = false;
                foreach ($context->getArguments() as $argumentName => $argumentValue)
                {
                    // Если аргумент передан при вызове, сохраняем его значение.
                    if ($argumentName == $argumentDefault->getText())
                    {
                        $newArguments->set($argumentName, $argumentValue);
                        $found = true;
                        break;
                    }
                }
                unset ($argumentName, $argumentValue);
                if (! $found)
                {
                    // Дефолтные значения ещё надо скомпилировать.
                    $subVariables = new OxtExpressionValueNamedList();
                    $subArguments = new OxtExpressionValueNamedList();
                    $subArguments->finalize();
                    $childNodes = $argumentDefault->getChildNodes();
                    $subContext = new OxtEvaluationContext(
                        $context->getDocument(),
                        $context->getContextNode(),
                        $context->getContextPosition(),
                        $context->getContextSize(),
                        $context->getCurrentNode(),
                        $subArguments,
                        $context->getConstants(),
                        $subVariables,
                        $context->getTemplates()
                    );
                    $value = self::calculateVariableValue($childNodes, $subContext);
                    $newArguments->set($argumentDefault->getText(), $value);
                    
                    unset ($subVariables, $subArguments, $childNodes, $value, $subContext);
                }
                unset($found);
            }
            unset ($argumentDefault);
            $newArguments->finalize();

            $newVariables = new OxtExpressionValueNamedList();
            foreach ($node->getChildNodes() as $child)
            {
                foreach (self::compileNode (
                    $child,
                    new OxtEvaluationContext(
                        $context->getDocument(),
                        $context->getContextNode(),
                        $context->getContextPosition(),
                        $context->getContextSize(),
                        $context->getCurrentNode(),
                        $newArguments,
                        $context->getConstants(),
                        $newVariables,
                        $context->getTemplates()
                    )
                ) as $add)
                {
                    $return->push($add);
                }
                unset ($add);
            }
            unset ($newArguments, $newVariables, $child);
        }

        // $variable=
        elseif ($nodeType instanceof OxtCodeNodeTypeVariable)
        {
            $value = self::calculateVariableValue($node->getChildNodes(), $context);
            
            // Поскольку объект "переменные" возвращается по ссылке, следующее действие сохраняет изменения в контексте.
            $context->getVariables()->set($node->getText(), $value);
            unset ($newVariable);
        }

        // @attribute=
        elseif ($nodeType instanceof OxtCodeNodeTypeAttribute)
        {
            throw new OxtExceptionCompile(
                'Attributes are not implemented yet',
                OxtExceptionCompile::CODE_NOT_IMPLEMENTED
            );
        }

        // ^style=
        elseif ($nodeType instanceof OxtCodeNodeTypeStyle)
        {
            throw new OxtExceptionCompile(
                'Styles are not implemented yet',
                OxtExceptionCompile::CODE_NOT_IMPLEMENTED
            );
        }

        // !if {} ()
        elseif ($nodeType instanceof OxtCodeNodeTypeIf)
        {
            if ($node->hasChildNodes())
            {
                $test = self::calculateOperator($node->getSelect(), $context);
                if (! $test instanceof OxtExpressionValueInterface)
                {
                    throw new OxtInternalException(
                        'Calculate expression did not return expression value',
                        OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                    );
                }
                if ($test->toBoolean()->getValue())
                {
                    foreach ($node->getChildNodes() as $child)
                    {
                        foreach (self::compileNode ($child, $context) as $add)
                        {
                            $return->push($add);
                        }
                        unset ($add);
                    }
                    unset ($child);
                }
                unset ($test);
            }
        }

        // !while {} ()
        elseif ($nodeType instanceof OxtCodeNodeTypeWhile)
        {
            // TODO
            throw new OxtExceptionCompile(
                'While loops are not implemented yet',
                OxtExceptionCompile::CODE_NOT_IMPLEMENTED
            );
        }

        // !for {} ()
        elseif ($nodeType instanceof OxtCodeNodeTypeFor)
        {
            if ($node->hasChildNodes())
            {
                $select = self::calculateOperator($node->getSelect(), $context);
                if (! $select instanceof OxtExpressionValueInterface)
                {
                    throw new OxtInternalException(
                        'Calculate expression did not return expression value',
                        OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                    );
                }
                if (! $select->getType() instanceof OxtExpressionValueTypeNodeset)
                {
                    throw new OxtExceptionCompile(
                        'Select expression in for loop did not return nodeset',
                        OxtExceptionCompile::CODE_INVALID_EXPRESSION_TPYE
                    );
                }
                $list = $select->getValue();
                $newContextSize = count($list);
                $newContextPosition = 0;
                foreach ($list as $newContextNode)
                {
                    ++$newContextPosition;
                    $newContextElement = new DOMElementOrRoot($newContextNode);
                    foreach ($node->getChildNodes() as $child)
                    {
                        foreach (self::compileNode(
                            $child,
                            new OxtEvaluationContext(
                                $context->getDocument(),
                                $newContextElement,
                                $newContextSize,
                                $newContextPosition,
                                $newContextElement,
                                $context->getArguments(),
                                $context->getConstants(),
                                $context->getVariables(),
                                $context->getTemplates()
                            )
                        ) as $add)
                        {
                            $return->push($add);
                        }
                        unset ($add);
                    }
                    unset ($child);
                }
                unset ($select, $newContextNode);
            }
        }

        // !choose (!when{}() !otherwise())
        elseif ($nodeType instanceof OxtCodeNodeTypeChoose)
        {
            if ($node->hasChildNodes())
            {
                foreach ($node->getChildNodes() as $case)
                {
                    $caseType = $case->getType();
                    if ($caseType instanceof OxtCodeNodeTypeWhen)
                    {
                        $test = self::calculateOperator($case->getSelect(), $context);
                        if (! $test instanceof OxtExpressionValueInterface)
                        {
                            throw new OxtInternalException(
                                'Calculate expression did not return expression value',
                                OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                            );
                        }
                        if ($test->toBoolean()->getValue())
                        {
                            foreach ($case->getChildNodes() as $child)
                            {
                                foreach (self::compileNode ($child, $context) as $add)
                                {
                                    $return->push($add);
                                }
                                unset ($add);
                            }
                            unset ($child, $test);
                            break;
                        }
                    }
                    elseif ($caseType instanceof OxtCodeNodeTypeOtherwise)
                    {
                        foreach ($case->getChildNodes() as $child)
                        {
                            foreach (self::compileNode ($child, $context) as $add)
                            {
                                $return->push($add);
                            }
                            unset ($add);
                        }
                        unset ($child, $test);
                        break;
                    }
                    unset ($caseType);
                }
            }
        }

        // 'string' or "string"
        elseif ($nodeType instanceof OxtCodeNodeTypeString)
        {
            $output = new OxtOutputNode(
                new OxtOutputNodeTypeString,
                $node->getText()
            );
            $return->push($output);
            unset ($output);
        }

        // 3.1415 -- number
        elseif ($nodeType instanceof OxtCodeNodeTypeNumber)
        {
            $return->push(new OxtOutputNode(
                new OxtOutputNodeTypeString,
                $node->getText()
            ));
        }

        // {expression}
        elseif ($nodeType instanceof OxtCodeNodeTypeExpression)
        {
            $value = self::calculateOperator($node->getSelect(), $context);
            $output = new OxtOutputNode(
                new OxtOutputNodeTypeString,
                $value->toString()->getValue()
            );
            $return->push($output);
            unset ($output);
        }

        // call(function)
        elseif ($nodeType instanceof OxtCodeNodeTypeCallFunction)
        {
            // TODO
            throw new OxtExceptionCompile(
                'Function calls are not implemented yet',
                OxtExceptionCompile::CODE_NOT_IMPLEMENTED
            );
        }

        // &template call
        elseif ($nodeType instanceof OxtCodeNodeTypeCallTemplate)
        {
            $templateName = $node->getText();
            if (! $context->getTemplates()->exists($templateName))
            {
                throw new OxtExceptionCompile(
                    'Call of undefined template: "' . $templateName . '"',
                    OxtExceptionCompile::CODE_CALL_UNDEFINED_TEMPLATE
                );
            }

            // Скомпилировать передаваемые аргументы.
            $newArguments = new OxtExpressionValueNamedList();
            foreach ($node->getArgumentNodes() as $argumentNode)
            {
                $newArguments->set(
                    $argumentNode->getText(),
                    self::calculateVariableValue($argumentNode->getChildNodes(), $context)
                );
            }
            unset ($argumentNode);
            $newArguments->finalize();
            
            $newVariables = new OxtExpressionValueNamedList();
            foreach (self::compileNode (
                $context->getTemplates()->get($templateName),
                new OxtEvaluationContext(
                    $context->getDocument(),
                    $context->getContextNode(),
                    $context->getContextPosition(),
                    $context->getContextSize(),
                    $context->getCurrentNode(),
                    $newArguments,
                    $context->getConstants(),
                    $newVariables,
                    $context->getTemplates()
                )
            ) as $add)
            {
                $return->push($add);
            }
            unset ($newVariables, $add);
        }

        // %tag
        elseif ($nodeType instanceof OxtCodeNodeTypeTag)
        {
            // TODO
            throw new OxtExceptionCompile(
                'Tags are not implemented yet',
                OxtExceptionCompile::CODE_NOT_IMPLEMENTED
            );
        }

        // Части выражений. Условно называются "операторами", хотя не все из них являются ими в прямом смысле этого слова.
        elseif ($nodeType instanceof OxtOperatorType)
        {
            throw new OxtExceptionCompile(
                'Node ' . get_class($nodeType) . ' is an operator, not allowed at output level',
                OxtExceptionCompile::CODE_ILLEGAL_NODE_TYPE
            );
        }

        // WTF
        else
        {
            throw new OxtExceptionCompile(
                'Unknown node type: ' . get_class($nodeType),
                OxtExceptionCompile::CODE_UNKNOWN_NODE_TYPE
            );
        }

        $return->finalize();
        return $return;
    }

    /**
     *  @param  OxtCodeNodeInterface            $node       Должен обладать типом OxtOperatorType.
     *  @param  OxtEvaluationContextInterface   $context    Текущее положение в документе, значения переменных итд.
     *                                                      В отличие от self::compileNode() и от $context->contextNode,
     *                                                      $context->currentNode передаётся внутри выражения без изменений.
     *  @return OxtExpressionValueInterface
    **/
    protected static function calculateOperator(
        OxtCodeNodeInterface            $node,
        OxtEvaluationContextInterface   $context
    ) {
        $nodeType = $node->getType();
        if ($nodeType instanceof OxtCodeNodeTypeExpression)
        {
            // Хак, потому что мне лень выяснять, где как должно вызываться.
            return self::calculateOperator($node->getSelect(), $context);
        }
        
        if (! $nodeType instanceof OxtOperatorType)
        {
            throw new OxtExceptionCompile(
                'Node ' . get_class($nodeType) . ' is not an operator, not allowed in expressions',
                OxtExceptionCompile::CODE_ILLEGAL_NODE_TYPE
            );
        }
        
        // or, and
        if ($nodeType instanceof OxtOperatorTypeLogicalOr ||
            $nodeType instanceof OxtOperatorTypeLogicalAnd
        ) {
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 2)
            {
                throw new OxtExceptionCompile(
                    get_class($nodeType) . ' must have exactly 2 child nodes',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $leftNode  = $childNodes->getItem(0);
            $rightNode = $childNodes->getItem(1);
            
            $leftValue = self::calculateOperator($leftNode, $context)->toBoolean();
            
            // ленивое вычисление.
            if ($nodeType instanceof OxtOperatorTypeLogicalOr)
            {
                if ($leftValue->getValue())
                {
                    return $leftValue;
                }
            }
            elseif ($nodeType instanceof OxtOperatorTypeLogicalAnd)
            {
                if (! $leftValue->getValue()) 
                {
                    return new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), false);
                }
            }
            return self::calculateOperator($rightNode, $context)->toBoolean();
        }
            
        // =, !=, <=, <, >=, >
        if ($nodeType instanceof OxtOperatorTypeCompareEqual ||
            $nodeType instanceof OxtOperatorTypeCompareNotEqual ||
            $nodeType instanceof OxtOperatorTypeCompareLessOrEqual ||
            $nodeType instanceof OxtOperatorTypeCompareStrictlyLess ||
            $nodeType instanceof OxtOperatorTypeCompareGreaterOrEqual ||
            $nodeType instanceof OxtOperatorTypeCompareStrictlyGreater
        ) {
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 2)
            {
                throw new OxtExceptionCompile(
                    get_class($nodeType) . ' must have exactly 2 child nodes',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $leftNode  = $childNodes->getItem(0);
            $rightNode = $childNodes->getItem(1);
            
            $leftValue  = self::calculateOperator($leftNode, $context);
            $rightValue = self::calculateOperator($rightNode, $context);
            unset ($leftNode, $rightNode);
            
            $leftType  = $leftValue ->getType();
            $rightType = $rightValue->getType();
            
            $trueValue  = new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), true);
            $falseValue = new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), false);
            
            // First, comparisons that involve node-sets are defined in terms of comparisons that do not involve node-sets;
            // this is defined uniformly for =, !=, <=, <, >= and >.
            if ($rightType instanceof OxtExpressionValueTypeNodeset)
            {
                if ($leftType instanceof OxtExpressionValueTypeNodeset)
                {
                    /**
                     * If both objects to be compared are node-sets, then the comparison will be true if and only if
                     * there is a node in the first node-set and a node in the second node-set such that
                     * the result of performing the comparison on the string-values of the two nodes is true.
                    **/
                    foreach ($leftValue->getValue() as $leftNode)
                    {
                        $leftString = DOMStringValue::get($leftNode);
                        foreach ($rightValue->getValue() as $rightNode)
                        {
                            $rightString = DOMStringValue::get($rightNode);
                            if (self::performComparison($nodeType, $leftString, $rightString))
                            {
                                return $trueValue;
                            }
                        }
                    }
                    return $falseValue;
                }
                
                // Поменяем местами, чтобы считать в дальнейшем, что нодсет находится слева.
                $temp = $leftValue; $leftValue = $rightValue; $rightValue = $temp;
                $temp = $leftType; $leftType = $rightType; $rightType = $temp;
                unset ($temp);
            }
            
            if ($leftType instanceof OxtExpressionValueTypeNodeset)
            {
                if ($rightType instanceof OxtExpressionValueTypeNumber)
                {
                    /**
                     * If one object to be compared is a node-set and the other is a number, 
                     * then the comparison will be true if and only if there is a node in the node-set such that 
                     * the result of performing the comparison on the number to be compared and 
                     * on the result of converting the string-value of that node to a number using the number function is true.
                    **/
                    $rightNumber = $rightValue->getValue();
                    foreach ($leftValue->getValue() as $leftNode)
                    {
                        $leftString = DOMStringValue::get($leftNode);
                        $arguments = new OxtExpressionValueList();
                        $arguments->push (new OxtExpressionValue (
                            new OxtExpressionValueTypeString,
                            $leftString
                        ));
                        $arguments->finalize();
                        $leftNumber = self::calculateCoreFunction('number', $arguments);
                        if (self::performComparison($nodeType, $leftNumber->getValue(), $rightNumber))
                        {
                            return $trueValue;
                        }
                    }
                    return $falseValue;
                }
                
                if ($rightType instanceof OxtExpressionValueTypeString)
                {
                    /**
                     * If one object to be compared is a node-set and the other is a string, 
                     * then the comparison will be true if and only if there is a node in the node-set such that 
                     * the result of performing the comparison on the string-value of the node and the other string is true.
                    **/
                    $rightString = $rightValue->getValue();
                    foreach ($leftValue->getValue() as $leftNode)
                    {
                        $leftString = DOMStringValue::get($leftNode);
                        if (self::performComparison($nodeType, $leftString, $rightString))
                        {
                            return $trueValue;
                        }
                    }
                    return $falseValue;
                }
                
                if ($rightType instanceof OxtExpressionValueTypeBoolean)
                {
                    /**
                     * If one object to be compared is a node-set and the other is a boolean, 
                     * then the comparison will be true if and only if the result of performing 
                     * the comparison on the boolean and on the result of converting the node-set 
                     * to a boolean using the boolean function is true.
                    **/
                    $rightBoolean = $rightValue->getValue();
                    $arguments = new OxtExpressionValueList();
                    $arguments->push ($rightValue);
                    $arguments->finalize();
                    $leftBoolean = self::calculateCoreFunction('boolean', $arguments);
                    if (self::performComparison($nodeType, $leftBoolean->getValue(), $rightBoolean))
                    {
                        return $trueValue;
                    }
                    return $falseValue;
                }
            }
            
            // Second, comparisons that do not involve node-sets are defined for = and !=. 
            if ($nodeType instanceof OxtOperatorTypeCompareEqual ||
                $nodeType instanceof OxtOperatorTypeCompareNotEqual
            ) {
                if ($leftType instanceof OxtExpressionValueTypeBoolean ||
                    $rightType instanceof OxtExpressionValueTypeBoolean
                ) {
                    // If at least one object to be compared is a boolean, then each object to be compared
                    // is converted to a boolean as if by applying the boolean function. 
                    if (! $leftType instanceof OxtExpressionValueTypeBoolean)
                    {
                        $arguments = new OxtExpressionValueList();
                        $arguments->push ($leftValue);
                        $arguments->finalize();
                        $leftValue = self::calculateCoreFunction('boolean', $arguments);
                    }
                    if (! $rightType instanceof OxtExpressionValueTypeBoolean)
                    {
                        $arguments = new OxtExpressionValueList();
                        $arguments->push ($rightValue);
                        $arguments->finalize();
                        $rightValue = self::calculateCoreFunction('boolean', $arguments);
                    }
                }
                
                if ($leftType instanceof OxtExpressionValueTypeNumber ||
                    $rightType instanceof OxtExpressionValueTypeNumber
                ) {
                    // if at least one object to be compared is a number, then each object to be compared 
                    // is converted to a number as if by applying the number function
                    if (! $leftType instanceof OxtExpressionValueTypeNumber)
                    {
                        $arguments = new OxtExpressionValueList();
                        $arguments->push ($leftValue);
                        $arguments->finalize();
                        $leftValue = self::calculateCoreFunction('number', $arguments);
                    }
                    if (! $rightType instanceof OxtExpressionValueTypeNumber)
                    {
                        $arguments = new OxtExpressionValueList();
                        $arguments->push ($rightValue);
                        $arguments->finalize();
                        $rightValue = self::calculateCoreFunction('number', $arguments);
                    }
                }
                
                if ($leftType instanceof OxtExpressionValueTypeString ||
                    $rightType instanceof OxtExpressionValueTypeString
                ) {
                    // if at least one object to be compared is a number, then each object to be compared 
                    // is converted to a number as if by applying the number function
                    if (! $leftType instanceof OxtExpressionValueTypeString)
                    {
                        $arguments = new OxtExpressionValueList();
                        $arguments->push ($leftValue);
                        $arguments->finalize();
                        $leftValue = self::calculateCoreFunction('string', $arguments);
                    }
                    if (! $rightType instanceof OxtExpressionValueTypeString)
                    {
                        $arguments = new OxtExpressionValueList();
                        $arguments->push ($rightValue);
                        $arguments->finalize();
                        $rightValue = self::calculateCoreFunction('string', $arguments);
                    }
                }
                
                if (self::performComparison($nodeType, $leftValue->getValue(), $rightValue->getValue()))
                {
                    return $trueValue;
                }
                return $falseValue;
            }
            
            // When neither object to be compared is a node-set and the operator is <=, <, >= or >, 
            // then the objects are compared by converting both objects to numbers and comparing the numbers.
            if ($nodeType instanceof OxtOperatorTypeCompareLessOrEqual ||
                $nodeType instanceof OxtOperatorTypeCompareStrictlyLess ||
                $nodeType instanceof OxtOperatorTypeCompareGreaterOrEqual ||
                $nodeType instanceof OxtOperatorTypeCompareStrictlyGreater
            ) {
                if (! $leftType instanceof OxtExpressionValueTypeNumber)
                {
                    $arguments = new OxtExpressionValueList();
                    $arguments->push ($leftValue);
                    $arguments->finalize();
                    $leftValue = self::calculateCoreFunction('number', $arguments);
                }
                if (! $rightType instanceof OxtExpressionValueTypeNumber)
                {
                    $arguments = new OxtExpressionValueList();
                    $arguments->push ($rightValue);
                    $arguments->finalize();
                    $rightValue = self::calculateCoreFunction('number', $arguments);
                }
                if (self::performComparison($nodeType, $leftValue->getValue(), $rightValue->getValue()))
                {
                    return $trueValue;
                }
                return $falseValue;
            }
            
            throw new OxtExceptionCompile(
                'Could not handle comparison for operands of types ' . get_class ($leftType) . ' and ' . get_class($rightType),
                OxtExceptionCompile::CODE_ILLEGAL_NODE_TYPE
            );
        }
        
        // +, -, *, /, %
        if ($nodeType instanceof OxtOperatorTypeArithmeticAddition ||
            $nodeType instanceof OxtOperatorTypeArithmeticSubstraction ||
            $nodeType instanceof OxtOperatorTypeArithmeticMultiplication ||
            $nodeType instanceof OxtOperatorTypeArithmeticDivision ||
            $nodeType instanceof OxtOperatorTypeArithmeticModulo
        ) {
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 2)
            {
                throw new OxtExceptionCompile(
                    'CompareEqual operator must have exactly 2 child nodes',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $leftNode  = $childNodes->getItem(0);
            $rightNode = $childNodes->getItem(1);
            
            $leftValue  = self::calculateOperator($leftNode, $context);
            $rightValue = self::calculateOperator($rightNode, $context);
            unset ($leftNode, $rightNode);
            
            if (! $leftValue->getType() instanceof OxtExpressionValueTypeNumber)
            {
                $arguments = new OxtExpressionValueList();
                $arguments->push ($leftValue);
                $arguments->finalize();
                $leftValue = self::calculateCoreFunction('number', $arguments);
                unset ($arguments);
            }
            if (! $rightValue->getType() instanceof OxtExpressionValueTypeNumber)
            {
                $arguments = new OxtExpressionValueList();
                $arguments->push ($rightValue);
                $arguments->finalize();
                $rightValue = self::calculateCoreFunction('number', $arguments);
                unset ($arguments);
            }
            
            if ($nodeType instanceof OxtOperatorTypeArithmeticAddition)
            {
                $result = $leftValue->getValue() + $rightValue->getValue();
            }
            elseif ($nodeType instanceof OxtOperatorTypeArithmeticSubstraction)
            {
                $result = $leftValue->getValue() - $rightValue->getValue();
            }
            elseif ($nodeType instanceof OxtOperatorTypeArithmeticMultiplication)
            {
                $result = $leftValue->getValue() * $rightValue->getValue();
            }
            elseif ($nodeType instanceof OxtOperatorTypeArithmeticDivision)
            {
                if ($rightValue->getValue() < 1e-10)
                {
                    throw new OxtExceptionCompile(
                        'Division by zero',
                        OxtExceptionCompile::CODE_DIVISION_BY_ZERO
                    );
                }
                $result = $leftValue->getValue() / $rightValue->getValue();
            }
            elseif ($nodeType instanceof OxtOperatorTypeArithmeticModulo)
            {
                if ($rightValue->getValue() < 1e-10)
                {
                    throw new OxtExceptionCompile(
                        'Division by zero',
                        OxtExceptionCompile::CODE_DIVISION_BY_ZERO
                    );
                }
                $result = $leftValue->getValue() % $rightValue->getValue();
            }
            else
            {
                throw new OxtExceptionCompile(
                    'Could not perform arithmetic operation ' . get_class ($nodeType),
                    OxtExceptionCompile::CODE_ILLEGAL_NODE_TYPE
                );
            }
            return new OxtExpressionValue (
                new OxtExpressionValueTypeNumber,
                $result
            );
        }
        
        // "<path>/<step>"
        if ($nodeType instanceof OxtOperatorTypeRelativeStep)
        {
            /**
             * Вычислить <path>/<step> означает сначала вычислить <path> в текущем контексте,
             * а затем вычислить <step> в контексте каждого возвращённого элемента.
             *
             * <path> и <step> лежат в childNodes.
             *
             * Возвращает NodeSet.
            **/
            
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 2)
            {
                throw new OxtExceptionCompile(
                    'RelativeStep operator must have exactly 2 child nodes',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $pathNode = $childNodes->getItem(0);
            $stepNode = $childNodes->getItem(1);
            
            $pathValue = self::calculateOperator($pathNode, $context);
            if (! $pathValue instanceof OxtExpressionValueInterface)
            {
                throw new OxtInternalException(
                    'Calculate expression did not return expression value',
                    OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                );
            }
            if (! $pathValue->getType() instanceof OxtExpressionValueTypeNodeset)
            {
                throw new OxtExceptionCompile(
                    'Path part of RelativeStep operator did not return a node set',
                    OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
                );
            }
            
            // Сейчас $pathValue->value содержит array(DOMNode), и каждый из его элементов надо подставить в качестве
            // контекста для вычисления <step>.
            $list = $pathValue->getValue();
            $newContextPosition = 0;
            $newContextSize = count($list);
            $returnList = array();
            foreach ($list as $newContextNode)
            {
                ++$newContextPosition;
                $newContextElement = new DOMElementOrRoot($newContextNode);
                $stepValue = self::calculateOperator (
                    $stepNode,
                    new OxtEvaluationContext (
                        $context->getDocument(),
                        $newContextElement,
                        $newContextPosition,
                        $newContextSize,
                        $context->getCurrentNode(),
                        $context->getArguments(),
                        $context->getConstants(),
                        $context->getVariables(),
                        $context->getTemplates()
                    )
                );
                if (! $stepValue instanceof OxtExpressionValueInterface)
                {
                    throw new OxtInternalException(
                        'Calculate expression did not return expression value',
                        OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                    );
                }
                if (! $stepValue->getType() instanceof OxtExpressionValueTypeNodeset)
                {
                    throw new OxtExceptionCompile(
                        'Step part of RelativeStep operator did not return a node set',
                        OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
                    );
                }
                foreach ($stepValue->getValue() as $newReturnNode)
                {
                    $returnList[] = $newReturnNode;
                }
            }
            
            return new OxtExpressionValue (
                new OxtExpressionValueTypeNodeset(),
                $returnList
            );
        }
        
        // "/<step>"
        if ($nodeType instanceof OxtOperatorTypeAbsoluteStep)
        {
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 1)
            {
                throw new OxtExceptionCompile(
                    'AbsoluteStep operator must have exactly 1 child node',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $stepNode = $childNodes->getItem(0);
            
            $newContextPosition = 1;
            $newContextSize = 1;
            $returnList = array();
            $document = $context->getDocument();
            $stepValue = self::calculateOperator (
                $stepNode,
                new OxtEvaluationContext (
                    $document,
                    new DOMElementOrRoot(), // специальное значение для корня.
                    $newContextPosition,
                    $newContextSize,
                    $context->getCurrentNode(),
                    $context->getArguments(),
                    $context->getConstants(),
                    $context->getVariables(),
                    $context->getTemplates()
                )
            );
            if (! $stepValue instanceof OxtExpressionValueInterface)
            {
                throw new OxtInternalException(
                    'Calculate expression did not return expression value',
                    OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                );
            }
            if (! $stepValue->getType() instanceof OxtExpressionValueTypeNodeset)
            {
                throw new OxtExceptionCompile(
                    'AbsoluteStep operator did not return a node set',
                    OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
                );
            }
            foreach ($stepValue->getValue() as $newReturnNode)
            {
                $returnList[] = $newReturnNode;
            }
            
            return new OxtExpressionValue (
                new OxtExpressionValueTypeNodeset(),
                $returnList
            );
        }
        
        // Контейнер для Predicate, NameTest и NodeTypeTest. Введён из соображений разбора.
        // Просто передаёт вычисление единственному чайлду.
        if ($nodeType instanceof OxtOperatorTypeStep)
        {
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 1)
            {
                throw new OxtExceptionCompile(
                    'Step operator must have exactly 1 child node',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $childNode = $childNodes->getItem(0);
            return self::calculateOperator($childNode, $context);
        }
        
        // <node set>[<condition>]
        if ($nodeType instanceof OxtOperatorTypePredicate)
        {
            /**
             * Вычислить <node set>. В контексте каждого элемента вычислить <condition>,
             * и если значение приводится к булевой истине, то добавить элемент в возвращаемое значение.
             * Если значение является числом, то добавляется элемент с такой позицией.
             *
             * <node set> и <condition> являются чайлдами.
            **/
            
            $childNodes = $node->getChildNodes();
            if ($childNodes->getCount() != 2)
            {
                throw new OxtExceptionCompile(
                    'Predicate operator must have exactly 2 child nodes',
                    OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                );
            }
            $nodesetNode   = $childNodes->getItem(0);
            $conditionNode = $childNodes->getItem(1);
            
            $nodesetValue = self::calculateOperator($nodesetNode, $context);
            if (! $nodesetValue instanceof OxtExpressionValueInterface)
            {
                throw new OxtInternalException(
                    'Calculate expression did not return expression value',
                    OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                );
            }
            if (! $nodesetValue->getType() instanceof OxtExpressionValueTypeNodeset)
            {
                throw new OxtExceptionCompile(
                    'Predicate operator must only run on node sets',
                    OxtExceptionCompile::CODE_INVALID_EXPRESSION_TYPE
                );
            }
            
            // Теперь на каждом элементе в $nodesetValue вычисляем условие.
            $list = $nodesetValue->getValue();
            $newContextPosition = 0;
            $newContextSize = count($list);
            $returnList = array();
            foreach ($list as $newContextNode)
            {
                ++$newContextPosition;
                $newContextElement = new DOMElementOrRoot($newContextNode);
                $conditionValue = self::calculateOperator (
                    $conditionNode,
                    new OxtEvaluationContext (
                        $context->getDocument(),
                        $newContextElement,
                        $newContextPosition,
                        $newContextSize,
                        $context->getCurrentNode(),
                        $context->getArguments(),
                        $context->getConstants(),
                        $context->getVariables(),
                        $context->getTemplates()
                    )
                );
                if (! $conditionValue instanceof OxtExpressionValueInterface)
                {
                    throw new OxtInternalException(
                        'Calculate expression did not return expression value',
                        OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                    );
                }
                // Особый случай предикатов: если вычисляется в число, то это позиция, которую надо взять.
                if ($conditionValue->getType() instanceof OxtExpressionValueTypeNumber)
                {   
                    if ($conditionValue->getValue() == $newContextPosition)
                    {
                        $returnList[] = $newContextNode;
                    }
                }
                // Обычный случай: приводим к булевому значению.
                else
                {
                    if ($conditionValue->toBoolean()->getValue())
                    {
                        $returnList[] = $newContextNode;
                    }
                }
            }
            
            return new OxtExpressionValue (
                new OxtExpressionValueTypeNodeset(),
                $returnList
            );
        }
        
        // <axis>::<nodename>
        if ($nodeType instanceof OxtOperatorTypeNameTest)
        {
            $axis = $node->getAxis();
            $name = $node->getText();
            return self::calculateNodeTest($axis, $name, $context);
        }
        
        // <axis>::<nodetype>
        if ($nodeType instanceof OxtOperatorTypeNodeTypeTest)
        {
            $axis = $node->getAxis();
            $type = $node->getText();
            return self::calculateNodeTest($axis, $type . '()', $context);
        }
        
        // $variable
        if ($nodeType instanceof OxtOperatorTypeVariable)
        {
            // Порядок перебора области видимости: переменные, аргументы, константы.
            $varname = $node->getText();
            
            // Переменные.
            $variables = $context->getVariables();
            if ($variables->exists ($varname))
            {
                return $variables->get($varname);
            }
            
            // Аргументы.
            $arguments = $context->getArguments();
            if ($arguments->exists ($varname))
            {
                return $arguments->get($varname);
            }
            
            // Константы.
            $constants = $context->getConstants();
            if ($constants->exists ($varname))
            {
                return $constants->get($varname);
            }
            
            // Непонятно.
            throw new OxtExceptionCompile(
                'Undefined variable or constant name "' . $varname . '"',
                OxtExceptionCompile::CODE_UNDEFINED_VARIABLE
            );
        }
        
        // 31415 - пока что только целые.
        // TODO: добавить поддержку остальных чисел.
        if ($nodeType instanceof OxtOperatorTypeNumber)
        {
            return new OxtExpressionValue (
                new OxtExpressionValueTypeNumber(),
                intval($node->getText())
            );
        }
        
        // 'string' or "string"
        if ($nodeType instanceof OxtOperatorTypeString)
        {
            return new OxtExpressionValue (
                new OxtExpressionValueTypeString(),
                $node->getText()
            );
        }
        
        // <function>(<arguments>)
        if ($nodeType instanceof OxtOperatorTypeFunction)
        {
            $funcname = $node->getText();
            
            // Вычислим аргументы перед передачей в функцию.
            $arguments = new OxtExpressionValueList();
            foreach ($node->getArgumentNodes() as $argumentNode)
            {
                $arguments->push(self::calculateOperator($argumentNode, $context));
            }
            $arguments->finalize();
            
            // Мы точно не знаем, что за функция -- библиотечная или расширенная.
            // Поэтому сначала попытаемся выполнить расширенную.
            try
            {
                return self::calculateExtensionFunction($funcname, $arguments, $context);
            }
            catch (OxtExceptionCompile $e)
            {
                if ($e->getCode() != OxtExceptionCompile::CODE_CALL_UNDEFINED_FUNCTION)
                {
                    throw $e;
                }
                return self::calculateCoreFunction($funcname, $arguments, $context);
            }
            throw new OxtExceptionInternal(
                'Unreachable code',
                OxtExceptionInternal::CODE_UNREACHABLE_CODE
            );
        }
        
        /*
            class OxtOperatorTypeLogicalOr                  extends OxtOperatorTypeAbstract {}
            class OxtOperatorTypeLogicalAnd                 extends OxtOperatorTypeAbstract {}
            class OxtOperatorTypeCompareNotEqual            extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeCompareEqual               extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeCompareLessOrEqual         extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeCompareStrictlyLess        extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeCompareGreaterOrEqual      extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeCompareStrictlyGreater     extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeArithmeticAddition         extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeArithmeticSubstraction     extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeArithmeticMultiplication   extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeArithmeticDivision         extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeArithmeticModulo           extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeUnaryMinus                 extends OxtOperatorTypeAbstract {}
            class OxtOperatorTypeNodeSetUnion               extends OxtOperatorTypeAbstract {}
            class OxtOperatorTypeRelativeStep               extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeRelativeDescendantOrSelf   extends OxtOperatorTypeAbstract {}
            class OxtOperatorTypeAbsoluteStep               extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeAbsoluteDescendantOrSelf   extends OxtOperatorTypeAbstract {}

            class OxtOperatorTypeStep                       extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypePredicate                  extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeNameTest                   extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeNodeTypeTest               extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeVariable                   extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeString                     extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeNumber                     extends OxtOperatorTypeAbstract {} v
            class OxtOperatorTypeFunction                   extends OxtOperatorTypeAbstract {} v
        */
        
        // WTF
        throw new OxtExceptionCompile(
            'Unknown node type: ' . get_class($nodeType),
            OxtExceptionCompile::CODE_UNKNOWN_NODE_TYPE
        );
    }
    
    /**
     * Вспомогательная функция, позволяющая сэкономить на расписывании шести одинаковых кусков кода
     * для реализации операторов сравнения.
    **/
    protected static function performComparison (OxtOperatorType $operator, $a, $b)
    {
        if ($operator instanceof OxtOperatorTypeCompareEqual)
        {
            return $a == $b;
        }
        if ($operator instanceof OxtOperatorTypeCompareNotEqual)
        {
            return $a != $b;
        }
        if ($operator instanceof OxtOperatorTypeCompareLessOrEqual)
        {
            return $a <= $b;
        }
        if ($operator instanceof OxtOperatorTypeCompareStrictlyLess)
        {
            return $a < $b;
        }
        if ($operator instanceof OxtOperatorTypeCompareGreaterOrEqual)
        {
            return $a >= $b;
        }
        if ($operator instanceof OxtOperatorTypeCompareStrictlyGreater)
        {
            return $a > $b;
        }
    }
    
    /**
     * @return OxtExpressionValueInterface
    **/
    protected static function calculateNodeTest(
        OxtAxisType                     $axis,
                                        $nodetest,
        OxtEvaluationContextInterface   $context
    ) {
        $engine = new DOMXPath ($context->getDocument());
        $contextNode = $context->getContextNode();
        $axisSpecifier = $axis::SPECIFIER;
        try
        {
            if ($contextNode->isRoot())
            {
                $xpath = '/' . $axisSpecifier . '::' . $nodetest;
                $value = $engine->evaluate($xpath);
            }
            else
            {
                $xpath = $axisSpecifier . '::' . $nodetest;
                $value = $engine->evaluate($xpath, $contextNode->getElement());
            }
        }
        catch (Exception $e)
        {
            throw new OxtExceptionCompile(
                'Failed to evaluate xpath "' . $xpath . '": ' .
                '(' . get_class ($e) . ', ' . $e->getCode() . ') ' . $e->getMessage(),
                OxtExceptionCompile::CODE_FAILED_EVALUATE_XPATH,
                $e
            );
        }
        unset ($engine, $contextNode, $axisSpecifier);
        
        if ($value instanceof DOMNodeList)
        {
            $newValue = array();
            foreach ($value as $element)
            {
                $newValue[] = $element;
            }
            return new OxtExpressionValue(
                new OxtExpressionValueTypeNodeset,
                $newValue
            );
        }
        if (is_bool($value))
        {
            return new OxtExpressionValue(
                new OxtExpressionValueTypeBoolean,
                $value
            );
        }
        if (is_numeric($value))
        {
            return new OxtExpressionValue(
                new OxtExpressionValueTypeNumber,
                $value
            );
        }
        return new OxtExpressionValue(
            new OxtExpressionValueTypeString,
            strval($value)
        );
    }

    /**
     * Вычисляет значение переменной на основании её чайлднодов и контекста вычисления.
     * Если чайлднод один, то его значение берётся непосредственно. Иначе приводится к аутпуту.
     *
     * @return OxtExpressionValueInterface
    **/
    protected static function calculateVariableValue (
        OxtCodeBlockInterface           $childNodes,
        OxtEvaluationContextInterface   $context
    ) {
        // Если чайлд ровно один и он -- выражение, число или строка, то его значение
        // надо вернуть как есть, не приводя к аутпуту.
        if ($childNodes->getCount() == 1)
        {
            $onlyChild = $childNodes->getItem(0);
            $childType = $onlyChild->getType();
            if ($childType instanceof OxtCodeNodeTypeExpression)
            {
                $value = self::calculateOperator($onlyChild->getSelect(), $context);
                if (! $value instanceof OxtExpressionValueInterface)
                {
                    throw new OxtInternalException(
                        'Calculate expression did not return expression value',
                        OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                    );
                }
                return $value;
            }
            if ($childType instanceof OxtCodeNodeTypeString)
            {
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeString(),
                    $onlyChild->getText()
                );
            }
            if ($childType instanceof OxtCodeNodeTypeNumber)
            {
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeNumber(),
                    $onlyChild->getText()
                );
            }
            unset ($onlyChild, $childType);
        }
        
        // Чайлдов много, они вынужденно образуют аутпут.
        $output = new OxtOutputBlock();
        foreach ($childNodes as $child)
        {
            foreach (self::compileNode ($child, $context) as $add)
            {
                $output->push($add);
            }
            unset ($add);
        }
        unset ($child);
        $output->finalize();
        return new OxtExpressionValue (
            new OxtExpressionValueTypeOutput(),
            $output
        );
    }

    /**
     * Возвращает string-value нода в понимании xpath'а.
    **/
    protected static function nodeStringValue(DOMNode $node)
    {
        switch ($node->nodeType)
        {
            case XML_ELEMENT_NODE :
            {
                $return = '';
                foreach ($node->childNodes as $child)
                {
                    $return .= DOMStringValue::get($child);
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
    
    /**
     * Вычислить функцию из Core Library, определённой в XPath.
     *
     * НЕ класть сюда выдуманные функции. Для них есть calculateExtensionFunction.
    **/
    protected static function calculateCoreFunction (
        $functionName,
        OxtExpressionValueListInterface $arguments  = null,
        OxtEvaluationContextInterface   $context    = null
    ) {
        // Сформировать аргументы ($argument, $haystack, $needle etc) для некоторых общих случаев.
        switch ($functionName)
        {
            // Require context.
            case 'last' :
            case 'position' :
            {
                if (! $context)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' cannot be called in empty context',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                break;
            }
            
            // Accept 1 argument, which can be omitted and defaults to a node-set with the context node as its only member.
            case 'number' :
            case 'string' :
            {
                $argument = null;
                if ($arguments)
                {
                    $argumentCount = $arguments->getCount();
                    if ($argumentCount > 1)
                    {
                        throw new OxtExceptionCompile(
                            'Function ' . $functionName . ' must have 0 or 1 arguments',
                            OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                        );
                    }
                    if ($argumentCount > 0)
                    {
                        $argument = $arguments->getItem(0);
                    }
                    unset ($argumentCount);
                }
                if (! $argument)
                {
                    if (! $context)
                    {
                        throw new OxtExceptionCompile(
                            'Function ' . $functionName . ' has no argument and is called in empty context',
                            OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                        );
                    }
                    $argument = new OxtExpressionValue(
                        new OxtExpressionValueTypeNodeset,
                        array ($context->getContextNode()->getElement())
                    );
                }
                break;
            }
            
            // Require 1 argument.
            case 'count' :
            case 'boolean' :
            case 'not' :
            {
                if (! $arguments)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have an argument',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                if ($arguments->getCount() != 1)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have exactly 1 argument',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                $argument = $arguments->getItem(0);
                break;
            }
            
            // Require 2 string arguments, first one is used to search in, second one is used to search for.
            // Hence the names: haystack and needle.
            case 'starts-with' :
            case 'contains' :
            case 'substring-before' :
            case 'substring-after' :
            {
                if (! $arguments)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                if ($arguments->getCount() != 2)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have exactly 2 arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                $haystack = $arguments->getItem(0);
                $needle   = $arguments->getItem(1);
                break;
            }
        }
        
        // Применить стандартные алгоритмы.
        switch ($functionName)
        {
            // node set functions
            case 'last' :
            {
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeNumber,
                    $context->getContextSize()
                );
            }
            case 'position' :
            {
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeNumber,
                    $context->getContextPosition()
                );
            }
            case 'count' :
            {
                if (! $argument->getType() instanceof OxtExpressionValueTypeNodeset)
                {
                    throw new OxtExceptionCompile(
                        'Function count expects a nodeset as its argument, ' . get_class($argument->getType()) . ' given',
                        OxtExceptionCompile::CODE_ILLEGAL_TYPE_CONVERSION
                    );
                }
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeNumber,
                    count($argument->getValue())
                );
            }
            // string functions
            case 'string' :
            {
                return $argument->toString();
            }
            case 'translate' :
            {
                if (! $arguments)
                {
                    throw new OxtExceptionCompile(
                        'Function translate must have arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                if ($arguments->getCount() != 3)
                {
                    throw new OxtExceptionCompile(
                        'Function translate must have exactly 3 arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                /**
                 * Обозначим аргументы функции как $victim, $search, $replace.
                 * 
                 * The translate function returns $victim with occurrences of characters in $search replaced
                 * by the character at the corresponding position in $replace.
                 * If there is a character in $search with no character at a corresponding position in $replace
                 * (because $search is longer than $replace), then occurrences of that character in $victim are removed.
                 * If a character occurs more than once in $search, then the first occurrence determines the replacement character.
                 * If $replace is longer than $search, then excess characters are ignored.
                **/
                
                $victimValue  = $arguments->getItem(0);
                $searchValue  = $arguments->getItem(1);
                $replaceValue = $arguments->getItem(2);
                
                // Привести к строкам, если они не строки.
                if (! $victimValue->getType() instanceof OxtExpressionValueTypeString)
                {
                    $victimValue = $victimValue->toString();
                }
                if (! $searchValue->getType() instanceof OxtExpressionValueTypeString)
                {
                    $searchValue = $searchValue->toString();
                }
                if (! $replaceValue->getType() instanceof OxtExpressionValueTypeString)
                {
                    $replaceValue = $replaceValue->toString();
                }
                
                // Построим соответствие.
                $map = array();
                $searchString  = $searchValue ->getValue();
                $replaceString = $replaceValue->getValue();
                for ($i = 0, $ls = strlen ($searchString), $lr = strlen($replaceString); $i < $ls; ++$i)
                {
                    if (isset ($map[$searchString[$i]]))
                    {
                        continue;
                    }
                    if ($i < $lr)
                    {
                        $map[$searchString[$i]] = $replaceString[$i];
                    }
                    else
                    {
                        $map[$searchString[$i]] = '';
                    }
                }
                unset ($i, $ls, $lr);
                
                // Применим соответствие.
                $resultString = '';
                $victimString = $victimValue->getValue();
                for ($i = 0, $l = strlen($victimString); $i < $l; ++$i)
                {
                    if (isset ($map[$victimString[$i]]))
                    {
                        $resultString .= $map[$victimString[$i]];
                    }
                    else
                    {
                        $resultString .= $victimString[$i];
                    }
                }
                
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeString, 
                    $resultString
                );
            }
            case 'contains' :
            {
                // Привести к строкам, если они не строки.
                if (! $haystack->getType() instanceof OxtExpressionValueTypeString)
                {
                    $haystack = $haystack->toString();
                }
                if (! $needle->getType() instanceof OxtExpressionValueTypeString)
                {
                    $needle = $needle->toString();
                }
                
                if (false !== strpos ($haystack->getValue(), $needle->getValue()))
                {
                    return new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), true);
                }
                return new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), false);
            }
            // boolean functions
            case 'boolean' :
            {
                return $argument->toBoolean();
            }
            case 'not' :
            {
                return new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), ! $argument->toBoolean()->getValue());
            }
            // number functions
            case 'number' :
            {
                return $argument->toNumber();
            }
        }
        throw new OxtExceptionCompile(
            'Call to undefined function: ' . $functionName,
            OxtExceptionCompile::CODE_CALL_UNDEFINED_FUNCTION
        );
    }
    
    protected static function calculateExtensionFunction (
        $functionName,
        OxtExpressionValueListInterface $arguments  = null,
        OxtEvaluationContextInterface   $context    = null
    ) {
        switch ($functionName)
        {
            // string functions
            case 'convert-case' :
            {
                if (! $arguments)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                if ($arguments->getCount() != 2)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have exactly 2 arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                
                $victim = $arguments->getItem(0)->toString();
                $mode   = $arguments->getItem(1)->toString();
                
                switch ($mode->getValue())
                {
                    case 'upper' : $modeMB = MB_CASE_UPPER; break;
                    case 'lower' : $modeMB = MB_CASE_LOWER; break;
                    case 'title' : $modeMB = MB_CASE_TITLE; break;
                    default : throw new OxtExceptionCompile(
                        'Unknown mode "' . $mode->getValue() . '" for case conversion',
                        OxtExceptionCompile::CODE_ILLEGAL_ARGUMENT_VALUE
                    );
                }
                return new OxtExpressionValue (
                    new OxtExpressionValueTypeString(),
                    mb_convert_case($victim->getValue(), $modeMB)
                );
            }
            case 'contains-cyrillic' :
            {
                if (! $arguments)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                if ($arguments->getCount() != 1)
                {
                    throw new OxtExceptionCompile(
                        'Function ' . $functionName . ' must have exactly 1 arguments',
                        OxtExceptionCompile::CODE_WRONG_ARGUMENT_COUNT
                    );
                }
                $argument = $arguments->getItem(0)->toString();
                
                if (preg_match ('/[\p{Cyrillic}]/', $argument->getValue()))
                {
                    return new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), true);
                }
                return new OxtExpressionValue (new OxtExpressionValueTypeBoolean(), false);
            }
        }
        
        throw new OxtExceptionCompile(
            'Call to undefined function: ' . $functionName,
            OxtExceptionCompile::CODE_CALL_UNDEFINED_FUNCTION
        );
    }
}
