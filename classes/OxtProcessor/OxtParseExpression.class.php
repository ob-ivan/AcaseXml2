<?php

require_once dirname(__FILE__) . '/OxtExpression.class.php';
require_once dirname(__FILE__) . '/OxtOperatorList.class.php';
require_once dirname(__FILE__) . '/OxtOperator.class.php';
require_once dirname(__FILE__) . '/OxtAxis.type.php';
require_once dirname(__FILE__) . '/OxtParenthesesStack.class.php';
require_once dirname(__FILE__) . '/OxtFreeZone.class.php';
require_once dirname(__FILE__) . '/OxtSubExpression.class.php';

class OxtParseExpression
{
    // fields //

    protected static $operators = null;

    // public //

    /**
     * @return OxtCodeNode | false
    **/
    public static function parseExpression (&$input)
    {
        $expression = self::readExpression ($input);
        if (! $expression)
        {
            return false;
        }

        $return = self::buildExpressionNode ($expression);
        if (! $return)
        {
            throw new OxtExceptionParse (
                'Error in expression: "' . $expression->getText() . '"',
                OxtExceptionParse::CODE_ERROR_IN_EXPRESSION
            );
        }
        
        // Обернуть в Expression, чтобы компайлер понимал, где переходить в типовый режим вычислений.
        return new OxtCodeNode (
            new OxtCodeNodeTypeExpression(),    // type
            null,                               // name
            $return                             // select
        );
    }

    // protected //

    /**
     *  @return OxtExpression | false
    **/
    protected static function readExpression (&$input)
    {
        if ($input[0] != '{')
        {
            return false;
        }
        $input = ltrim (substr ($input, 1));

        $expression = new OxtExpression();
        $parstack = new OxtParenthesesStack();
        $lastIndex = 0;
        $currentIndex = 0;
        $skip = false; // escape one character after a backslash (\) sign.

        for (; ! empty ($input); ++$currentIndex)
        {
            $currentCharacter = $input[0];
            $input = substr ($input, 1);
            if ($parstack->isEmpty() && $currentCharacter == '}')
            {
                break;
            }
            $expression->pushText ($currentCharacter);

            if ($skip)
            {
                $skip = false;
                continue;
            }
            $skip = false;

            $topCharacter = $parstack->getTopCharacter();
            if ($topCharacter == '\'')
            {
                if ($currentCharacter == '\\')
                {
                    $skip = true;
                    continue;
                }
                if ($currentCharacter == '\'')
                {
                    $previousIndex = $parstack->popStartIndex();
                    $expression->pushSubExpression(new OxtSubExpression('\'', $previousIndex, $currentIndex));
                    $lastIndex = $currentIndex + 1;
                }
                continue;
            }
            if ($topCharacter == '"')
            {
                if ($currentCharacter == '\\')
                {
                    $skip = true;
                    continue;
                }
                if ($currentCharacter == '"')
                {
                    $previousIndex = $parstack->popStartIndex();
                    $expression->pushSubExpression(new OxtSubExpression('"', $previousIndex, $currentIndex));
                    $lastIndex = $currentIndex + 1;
                }
                continue;
            }
            if ($currentCharacter == ')')
            {
                if ($topCharacter != '(')
                {
                    throw new OxtExceptionParse (
                        'Parentheses mismatch: ' . $topCharacter . ' and )',
                        OxtExceptionParse::CODE_MISMATCHING_PARENTHESES
                    );
                }
                $previousIndex = $parstack->popStartIndex();
                $expression->pushSubExpression(new OxtSubExpression('(', $previousIndex, $currentIndex));
                $lastIndex = $currentIndex + 1;
                continue;
            }
            if ($currentCharacter == ']')
            {
                if ($topCharacter != '[')
                {
                    throw new OxtExceptionParse (
                        'Parentheses mismatch: "' . $topCharacter . '" and "]" in expression "' . $expression->getText() . '"',
                        OxtExceptionParse::CODE_MISMATCHING_PARENTHESES
                    );
                }
                $previousIndex = $parstack->popStartIndex();
                $expression->pushSubExpression(new OxtSubExpression('[', $previousIndex, $currentIndex));
                $lastIndex = $currentIndex + 1;
                continue;
            }
            if ($currentCharacter == '}')
            {
                if ($topCharacter != '{')
                {
                    throw new OxtExceptionParse (
                        'Parentheses mismatch: ' . $topCharacter . ' and }',
                        OxtExceptionParse::CODE_MISMATCHING_PARENTHESES
                    );
                }
                $previousIndex = $parstack->popStartIndex();
                $expression->pushSubExpression(new OxtSubExpression('{', $previousIndex, $currentIndex));
                $lastIndex = $currentIndex + 1;
                continue;
            }
            if (preg_match ('/^[(\[{\'"]$/', $currentCharacter))
            {
                if ($lastIndex < $currentIndex && $parstack->isEmpty())
                {
                    $expression->pushFreeZone(new OxtFreeZone($lastIndex, $currentIndex - 1));
                }
                $parstack->push ($currentCharacter, $currentIndex);
                continue;
            }
        }
        if ($lastIndex < $currentIndex)
        {
            $expression->pushFreeZone(new OxtFreeZone($lastIndex, $currentIndex - 1));
        }

        $expression->finalize();
        return $expression;
    }

    protected static function loadDefaultOperators()
    {
        if (self::$operators instanceof OxtOperatorList)
        {
            return;
        }

        $operators = new OxtOperatorList();

        $operators->push (new OxtOperator ('or',    900, new OxtOperatorXFY, new OxtOperatorTypeLogicalOr));
        $operators->push (new OxtOperator ('and',   800, new OxtOperatorXFY, new OxtOperatorTypeLogicalAnd));
        $operators->push (new OxtOperator ('!=',    700, new OxtOperatorXFX, new OxtOperatorTypeCompareNotEqual));
        $operators->push (new OxtOperator ('=',     700, new OxtOperatorXFX, new OxtOperatorTypeCompareEqual));
        $operators->push (new OxtOperator ('<=',    600, new OxtOperatorXFX, new OxtOperatorTypeCompareLessOrEqual));
        $operators->push (new OxtOperator ('<',     600, new OxtOperatorXFX, new OxtOperatorTypeCompareStrictlyLess));
        $operators->push (new OxtOperator ('>=',    600, new OxtOperatorXFX, new OxtOperatorTypeCompareGreaterOrEqual));
        $operators->push (new OxtOperator ('>',     600, new OxtOperatorXFX, new OxtOperatorTypeCompareStrictlyGreater));
        $operators->push (new OxtOperator ('+',     500, new OxtOperatorXFY, new OxtOperatorTypeArithmeticAddition));
        $operators->push (new OxtOperator ('-',     500, new OxtOperatorXFY, new OxtOperatorTypeArithmeticSubstraction));
        $operators->push (new OxtOperator ('*',     400, new OxtOperatorXFY, new OxtOperatorTypeArithmeticMultiplication));
        $operators->push (new OxtOperator ('div',   400, new OxtOperatorXFY, new OxtOperatorTypeArithmeticDivision));
        $operators->push (new OxtOperator ('mod',   400, new OxtOperatorXFX, new OxtOperatorTypeArithmeticModulo));
        $operators->push (new OxtOperator ('-',     300, new OxtOperatorFX,  new OxtOperatorTypeUnaryMinus));
        $operators->push (new OxtOperator ('|',     200, new OxtOperatorXFY, new OxtOperatorTypeNodeSetUnion));
        $operators->push (new OxtOperator ('/',     100, new OxtOperatorYFS, new OxtOperatorTypeRelativeStep));
        $operators->push (new OxtOperator ('//',    100, new OxtOperatorYFS, new OxtOperatorTypeRelativeDescendantOrSelf));
        $operators->push (new OxtOperator ('/',      50, new OxtOperatorFS,  new OxtOperatorTypeAbsoluteStep));
        $operators->push (new OxtOperator ('//',     50, new OxtOperatorFS,  new OxtOperatorTypeAbsoluteDescendantOrSelf));
        
        self::$operators = $operators;
    }

    /**
     * @return OxtCodeNode | false
    **/
    protected static function buildExpressionNode (OxtExpressionInterface $expression)
    {
        // Разобрать простые выражения.

        // Первым разбираем Step, потому что в нём есть node(), text(), а FilterExpression принял бы их за функции.
        $node = self::parseStepExpression ($expression);
        if ($node)
        {
            return $node;
        }
        // print 'Expression "' . $expression->getText() . '" is not a step expression<br>'; // debug

        $node = self::parseFilterExpression ($expression);
        if ($node)
        {
            return $node;
        }
        // print 'Expression "' . $expression->getText() . '" is not a filter expression<br>'; // debug

        // Выражение не является простым, в нём содержатся операторы.
        $node = self::parseOperatorExpression ($expression);
        if ($node)
        {
            return $node;
        }
        // print 'Expression "' . $expression->getText() . '" is not an operator expression<br>'; // debug

        // Выражение не поддалось разбору.
        return false;
    }

    /**
     * PathExpression ::= LocationPath
     *                  | FilterExpression
     *                  | FilterExpression '/' RelativeLocationPath
     *                  | FilterExpression '//' RelativeLocationPath ;
     *
     * LocationPath   ::= RelativeLocationPath
     *                  | AbsoluteLocationPath ;
     *
     * FilterExpression ::= PrimaryExpression Predicate* ;
     *
     * RelativeLocationPath   ::= Step
     *                          | RelativeLocationPath '/' Step
     *                          | AbbreviatedRelativeLocationPath ;
     *
     * AbsoluteLocationPath   ::= '/' RelativeLocationPath?
     *                          | AbbreviatedAbsoluteLocationPath ;
     *
     * PrimaryExpression  ::= VariableReference
     *                      | '(' Expression ')'
     *                      | Literal
     *                      | Number
     *                      | FunctionCall ;
     *
     * Predicate ::= '[' Expression ']' ;
     *
     * Step   ::= AxisSpecifier NodeTest Predicate*
     *          | AbbreviatedStep ;
     *
     * AbbreviatedRelativeLocationPath ::= RelativeLocationPath '//' Step ;
     *
     * AbbreviatedAbsoluteLocationPath ::= '//' RelativeLocationPath ;
     *
     * AbbreviatedStep    ::= '.'       // транслируется в self::node()
     *                      | '..' ;    // транслируется в parent::node()
     *
     * Короче говоря, мы разбираем выражение, которое устроено таким образом:
     *  ( '.' | '..' | ( пусто |  FilterExpression | Step ) ( ('/' | '//') Step )* )
     * где "пусто" в начале означает "отсчёт от корня", а / и // -- бинарные операторы с левой ассоциативностью.
    **/

    /**
     * Попытаться разобрать Step, который состоит из шагов:
     *  1. ось = буквы '::', или '@', или отсутствует.
     *  2а. имя = буквы или '*'.
     *  или:
     *  2б. тип = 'text()' или 'node()' -- бывают ещё comment и processing-instruction, но я не хочу на них распыляться.
     *  3. предикаты = []*
     *
     *  @return OxtCodeNode | false
    **/
    protected static function parseStepExpression (OxtExpressionInterface $expression)
    {
        $fz = $expression->getFreeZoneList();
        $count = $fz->getCount();
        if ($count < 1)
        {
            // Хотя бы одна свободная зона должна быть.
            return false;
        }
        if ($count > 1)
        {
            // У степа не может быть свободных зон, кроме первой.
            return false;
        }
        // Следующий цикл служит для выбора первой свободной зоны в списке.
        // reset с Iterator'ом почему-то не работает.
        foreach ($fz as $fz1)
        {
            break;
        }
        $start = $fz1->getStartIndex();
        if ($start > 0)
        {
            // Степ может начинаться только со свободной зоны.
            return false;
        }
        unset ($start);
        $end = $fz1->getEndIndex();

        // Первичная проверка формы.
        $substr = $expression->getSubstr(0, $end);
        //                                2 axis
        //                      1 abbreviated 3 name     4 nodetest
        if (! preg_match ('/^(?:(\.{1,2})|(|@|([\w-]+)::)(\*|[a-z][\w-]*))$/i', $substr, $matches))
        {
            // Выражение вообще не является Step'ом.
            return false;
        }
        unset ($substr, $fz, $fz1, $end);
        
        $abbreviated    = isset ($matches[1]) ? $matches[1] : '';
        $axisSpecifier  = isset ($matches[2]) ? $matches[2] : '';
        $axisName       = isset ($matches[3]) ? $matches[3] : '';
        $nodeTest       = isset ($matches[4]) ? $matches[4] : '';
        unset ($matches);
        
        // Проверим, что затем идут только предикаты.
        // Заодно определим, является ли nodetest проверкой типа (typetest).
        // TODO: решить через checkPredicatesOnly.
        $firstIndex = false;
        $lastIndex = 0;
        $typetest = false;
        $firstRun = true;
        foreach ($expression->getSubExpressionList() as $sub)
        {
            if ($firstRun)
            {
                $firstRun = false;
                if ($sub->getOpenCharacter() == '(')
                {
                    $typetest = true;
                    $lastIndex = $sub->getEndIndex();
                    continue;
                }
            }
            
            // Cкипаем подвыражения не первого уровня.
            $end = $sub->getEndIndex();
            if ($lastIndex > 0 && $end < $lastIndex)
            {
                continue;
            }
            
            if ($sub->getOpenCharacter() != '[')
            {
                // Если на верхнем уровне оказались не квадратные скобки, то это не степ.
                return false;
            }
            if (! $firstIndex)
            {
                $firstIndex = $sub->getStartIndex();
            }
            $lastIndex = $end;
        }
        unset ($firstRun, $sub, $end);
        
        // ось
        if ($abbreviated == '.')
        {
            $axis = new OxtAxisSelf();
        }
        elseif ($abbreviated == '..')
        {
            $axis = new OxtAxisParent();
        }
        elseif ($axisSpecifier == '')
        {
            $axis = new OxtAxisChild();
        }
        elseif ($axisSpecifier == '@')
        {
            $axis = new OxtAxisAttribute();
        }
        else
        {
            switch ($axisName)
            {
                case 'ancestor' :
                {
                    $axis = new OxtAxisAncestor();
                    break;
                }
                case 'ancestor-or-self' :
                {
                    $axis = new OxtAxisAncestorOrSelf();
                    break;
                }
                case 'attribute' :
                {
                    $axis = new OxtAxisAttribute();
                    break;
                }
                case 'child' :
                {
                    $axis = new OxtAxisChild();
                    break;
                }
                case 'descendant' :
                {
                    $axis = new OxtAxisDescendant();
                    break;
                }
                case 'descendant-or-self' :
                {
                    $axis = new OxtAxisDescendantOrSelf();
                    break;
                }
                case 'following' :
                {
                    $axis = new OxtAxisFollowing();
                    break;
                }
                case 'following-sibling' :
                {
                    $axis = new OxtAxisFollowingSibling();
                    break;
                }
                case 'namespace' :
                {
                    $axis = new OxtAxisNamespace();
                    break;
                }
                case 'parent' :
                {
                    $axis = new OxtAxisParent();
                    break;
                }
                case 'preceding' :
                {
                    $axis = new OxtAxisPreceding();
                    break;
                }
                case 'preceding-sibling' :
                {
                    $axis = new OxtAxisPrecedingSibling();
                    break;
                }
                case 'self' :
                {
                    $axis = new OxtAxisSelf();
                    break;
                }
                default :
                {
                    throw new OxtExceptionParse(
                        'Unknown axis specifier: "' . $axisSpecifier . '"',
                        OxtExceptionParse::CODE_WRONG_AXIS_SPECIFIER
                    );
                }
            }
        }
        
        // nodetest бывает весьма разнообразным. В частности, он может иметь сокращённую запись.
        if ($abbreviated == '.' || $abbreviated == '..')
        {
            $typetest = true;
            $test = 'node';
        }
        // Если стоит *, то это проверка на соответствие типа главному типу оси.
        elseif ($nodeTest == '*')
        {
            /*
            // Это было бы правильно, если бы я вычислял оси и проверки на типы узлов самостоятельно.
            // Но поскольку я перепоручаю это дело классу DOMXPath, то приходится оставлять текстовый вид.
            
            $typetest = true;
            if ($axis instanceof OxtAxisAttribute)
            {
                $test = 'attribute';
            }
            elseif ($axis instanceof OxtAxisNamespace)
            {
                $test = 'namespace';
            }
            else
            {
                $test = 'element';
            }
            */
            $test = '*';
        }
        // Проверка типа.
        elseif ($typetest)
        {
            if ($nodeTest == 'node' || $nodeTest == 'text')
            {
                $test = $nodeTest;
            }
            else
            {
                // Незнакомое слово перед скобками может быть и вызовом функции -- но только в том случае,
                // если перед ним не было явного указания на ось.
                if (empty ($axisSpecifier))
                {
                    return false;
                }
                throw new OxtExceptionParse(
                    'Unknown node type test: "' . $nodeTest . '()"',
                    OxtExceptionParse::CODE_UNKNOWN_NODE_TYPE_TEST
                );
            }
        }
        // Проверка имени.
        else
        {
            $test = $nodeTest;
        }
        
        if ($typetest)
        {
            $nodeType = new OxtOperatorTypeNodeTypeTest();
        }
        else
        {
            $nodeType = new OxtOperatorTypeNameTest();
        }
        $node = new OxtCodeNode (
            $nodeType,  // CodeNodeType
            $test,      // name
            null,       // select
            null,       // childnodes
            null,       // attributes
            0,          // priority
            $axis       // axis
        );
                    
        // Разобрать предикаты, если они есть.
        if ($firstIndex > 0)
        {
            $slice = $expression->slice($firstIndex, $lastIndex);
            $node = self::parsePredicates ($node, $slice);
            if (! $node)
            {
                // Если в предикатах ересь, значит, всё выражение неправильное.
                return false;
            }
        }
        
        // Сформировать возвращаемый нод.
        $children = new OxtCodeBlock();
        $children->push ($node);
        $children->finalize();
        return new OxtCodeNode (
            new OxtOperatorTypeStep(),  // type
            null,                       // name
            null,                       // select
            $children                   // childnodes
        );
    }

    /**
     * Разобрать случаи:
     *  - выражение в скобках = фризоны нет, subExpressionList начинаются с () и за ними идут только []*
     *  - переменная = фризона выглядит как '$' буквы, потом только []*
     *  - строка в '' или в "" = фризоны нет, единственный subExpression.
     *  - число = единственная фризона специального устройства.
     *  - вызов функции = единственная фризона из букв, потом (), потом только []*.
     *
     * @return OxtCodeNode | false
    **/
    protected static function parseFilterExpression (OxtExpressionInterface $expression)
    {
        // Свободная зона -- важнейший критерий для правильно сформированного выражения.
        $fz = $expression->getFreeZoneList();
        $count = $fz->getCount();
        if ($count > 1)
        {
            // Не может быть свободных зон, кроме первой.
            return false;
        }
        if ($count < 1)
        {
            // Либо строка, либо группировочные скобки, поэтому список подвыражений должен быть непуст.
            $subExpressions = $expression->getSubExpressionList();
            if ($subExpressions->isEmpty())
            {
                return false;
            }
            foreach ($subExpressions as $sub1)
            {
                break;
            }
            $openCharacter = $sub1->getOpenCharacter();
            
            if ($openCharacter == '\'' || $openCharacter == '"')
            {
                // После строки не должно быть вообще ничего.
                if ($sub1->getEndIndex() < $expression->getTextLength() - 1)
                {
                    return false;
                }
                return new OxtCodeNode (
                    new OxtOperatorTypeString(),
                    $expression->getSubstr(1, -1)
                );
            }
            
            if ($openCharacter == '(')
            {
                $end = $sub1->getEndIndex();
                
                // Проверим, что после круглых скобок идут только предикаты.
                if (! self::checkPredicatesOnly ($expression, $end + 1))
                {
                    return false;
                }
                
                $subexpression = $expression->slice($sub1->getStartIndex() + 1, $end - 1);
                $node = self::buildExpressionNode($subexpression);
                if (! $node)
                {
                    return false;
                }
                
                // Разобрать предикаты, если они есть.
                $textLength = $expression->getTextLength();
                if ($end + 1 < $textLength - 1)
                {
                    $slice = $expression->slice($end + 1, $expression->getTextLength() - 1);
                    return self::parsePredicates ($node, $slice);
                }
                return $node;
            }
            
            // Никаких других вариантов без свободной зоны мы представить себе не можем.
            return false;
        }
        
        // Свободная зона есть и ровно одна. Может быть переменная, число и вызов функции.
        foreach ($fz as $fz1)
        {
            break;
        }
        $start  = $fz1->getStartIndex();
        if ($start > 0)
        {
            // Выражение должно начинаться со свободной зоны.
            return false;
        }
        unset ($start);
        $end    = $fz1->getEndIndex();
        $fztext = $expression->getSubstr(0, $end);
        
        // Переменная.
        if ($fztext[0] == '$')
        {
            // У переменной должно быть достойное имя.
            $varname = substr ($fztext, 1);
            if (! preg_match ('/^[\w-]+$/', $varname))
            {
                return false;
            }
            
            // После переменной могут идти только предикаты.
            if (! self::checkPredicatesOnly ($expression, $end + 1))
            {
                return false;
            }
            
            // Создадим нод.
            $node = new OxtCodeNode(
                new OxtOperatorTypeVariable(),
                $varname
            );
            
            // Разобрать предикаты, если они есть.
            $textLength = $expression->getTextLength();
            if ($end + 1 < $textLength - 1)
            {
                $slice = $expression->slice($end + 1, $expression->getTextLength() - 1);
                return self::parsePredicates ($node, $slice);
            }
            return $node;
        }
        
        // Числа пока только целые.
        // TODO: добавить все прочие числа.
        if (preg_match ('/^\d+$/', $fztext))
        {
            if ($end < $expression->getTextLength() - 1)
            {
                // после самого числа ничего не должно идти.
                return false;
            }
            return new OxtCodeNode (
                new OxtOperatorTypeNumber(),
                intval($fztext)
            );
        }
        
        // Функция.
        if (! preg_match ('/^([\w-]+)\s*$/', $fztext, $matches))
        {
            // У функции должно быть нормальное имя.
            return false;
        }
        $funcname = $matches[1];
        // У функции должны быть скобки.
        $subExpressions = $expression->getSubExpressionList();
        if ($subExpressions->isEmpty())
        {
            return false;
        }
        foreach ($subExpressions as $sub1)
        {
            break;
        }
        if ($sub1->getOpenCharacter() != '(')
        {
            return false;
        }
        $start = $sub1->getStartIndex();
        $end = $sub1->getEndIndex();
                
        // Проверим, что после круглых скобок идут только предикаты.
        if (! self::checkPredicatesOnly ($expression, $end + 1))
        {
            return false;
        }
        
        // Разобрать аргументы, если они есть.
        $argumentExpressions = $expression->slice($start + 1, $end - 1);
        $argumentNodes = new OxtCodeBlock();
        if ($argumentExpressions)
        {
            foreach ($argumentExpressions->split() as $argumentExpression)
            {
                $argumentNode = self::buildExpressionNode($argumentExpression);
                if (! $argumentNode)
                {
                    return false;
                }
                $argumentNodes->push ($argumentNode);
            }
        }
        $argumentNodes->finalize();
        $node = new OxtCodeNode (
            new OxtOperatorTypeFunction(),
            $funcname,
            null, // select
            null, // childnodes
            $argumentNodes
        );
        // Разобрать предикаты, если они есть.
        $textLength = $expression->getTextLength();
        if ($end + 1 < $textLength - 1)
        {
            $slice = $expression->slice($end + 1, $expression->getTextLength() - 1);
            return self::parsePredicates ($node, $slice);
        }
        return $node;
    }

    /**
     * Отвечает на вопрос, правда ли, что начиная с позиции $offset в выражении только []*.
    **/
    protected static function checkPredicatesOnly (OxtExpressionInterface $expression, $offset)
    {
        $lastIndex = 0;
        foreach ($expression->getSubExpressionList() as $sub)
        {
            // Пропускаем начальный кусок.
            if ($sub->getStartIndex() < $offset)
            {
                continue;
            }
            
            // Пропускаем подвыражения не первого уровня.
            $end = $sub->getEndIndex();
            if ($end < $lastIndex)
            {
                continue;
            }
            
            if ($sub->getOpenCharacter() != '[')
            {
                // Обещали же, что на верхнем уровне будут только предикаты!
                return false;
            }
            $lastIndex = $end;
        }
        return true;
    }
    
    /**
     * Получает на входе нод, на который будут навешиваться предикаты, и выражение, содержащее только их.
     * Возвращает собранное налево дерево разбора.
     *
     * @return OxtCodeNode
    **/
    protected static function parsePredicates (OxtCodeNodeInterface $node, OxtExpressionInterface $predicates)
    {
        $sublist = $predicates->getSubExpressionList();
        
        // Коли предикатов нет, то это именно то, что нас попросили.
        if ($sublist->isEmpty())
        {
            return $node;
        }
        
        // Если предикаты есть, то, поскольку ассоциативность левая, надо взять последний верхнего уровня.
        $lastIndex = 0;
        $lastPredicate = false;
        foreach ($sublist as $sub)
        {
            // Пропускаем подвыражения не первого уровня.
            $end = $sub->getEndIndex();
            if ($end < $lastIndex)
            {
                continue;
            }
            if ($sub->getOpenCharacter() == '[')
            {
                $lastPredicate = $sub;
            }
            $lastIndex = $end;
        }
        unset ($end, $lastIndex);
        if (! $lastPredicate)
        {
            // Не найдено ни одного подвыражения, являющегося предикатом.
            return false;
        }
        
        $start = $lastPredicate->getStartIndex();
        $expression = $predicates->slice($start + 1, $lastPredicate->getEndIndex() - 1);
        $predicateNode = self::buildExpressionNode ($expression);
        if (! $predicateNode)
        {
            // Если в предикате написана какая-то ересь, то выражение не годится.
            return false;
        }
        // Если есть ещё предикаты, то надо их разобрать рекурсивно.
        if ($start > 0)
        {
            $slice = $predicates->slice (0, $start - 1);
            $node = self::parsePredicates ($node, $slice);
            if (! $node)
            {
                return false;
            }
        }
        
        $children = new OxtCodeBlock();
        $children->push ($node);
        $children->push ($predicateNode);
        $children->finalize();
        return new OxtCodeNode (
            new OxtOperatorTypePredicate(),
            null,
            null,
            $children
        );
    }
    
    protected static function parseOperatorExpression (OxtExpressionInterface $expression)
    {
        if (! self::$operators)
        {
            self::loadDefaultOperators();
        }
        
        $text = $expression->getText();
        $textLength = $expression->getTextLength();
        $freeZones = $expression->getFreeZoneList();
        
        foreach (self::$operators as $operator)
        {
            $symbol         = $operator->getSymbol();
            $symbolLength   = strlen ($symbol);
            $associativity  = $operator->getAssociativity();
            $priority       = $operator->getPriority();
            
            foreach ($freeZones as $fz)
            {
                $start = $fz->getStartIndex();
                $end   = $fz->getEndIndex();
                
                for (
                    $symbolPosition = strpos ($text, $symbol, $start);
                    false !== $symbolPosition && $symbolPosition <= $end;
                    $symbolPosition = strpos ($text, $symbol, $symbolPosition + 1)
                ) {
                    $childNodes = new OxtCodeBlock();
                    if (! $associativity instanceof OxtAssociativityHasLeft && $symbolPosition > 0)
                    {
                        // Если левого аргумента нет, то слева ничего не должно быть.
                        continue;
                    }
                    if (! $associativity instanceof OxtAssociativityHasRight && $symbolPosition + $symbolLength < $textLength)
                    {
                        // Если правого аргумента нет, то справа ничего не должно быть.
                        continue;
                    }
                    
                    if ($associativity instanceof OxtAssociativityHasLeft)
                    {
                        if ($symbolPosition < 1)
                        {
                            // Не хватило места для левого операнда.
                            continue;
                        }
                        $leftExpression = $expression->slice (0, $symbolPosition - 1);
                        if ($leftExpression->isEmpty())
                        {
                            continue;
                        }
                        $leftNode = self::buildExpressionNode ($leftExpression);
                        unset ($leftExpression);

                        if (! $leftNode)
                        {
                            continue;
                        }
                        if (! $associativity instanceof OxtAssociativityGroupLeft &&
                            $leftNode->getPriority() === $priority
                        ) {
                            continue;
                        }

                        $childNodes->push ($leftNode);
                        unset ($leftNode);
                    }
                    if ($associativity instanceof OxtAssociativityHasRight)
                    {
                        if ($symbolPosition + $symbolLength > $textLength)
                        {
                            // Не хватило места для правого операнда.
                            continue;
                        }
                        $rightExpression = $expression->slice ($symbolPosition + $symbolLength, $textLength - 1);

                        if ($rightExpression->isEmpty())
                        {
                            continue;
                        }
                        $rightNode = self::buildExpressionNode ($rightExpression);
                        unset ($rightExpression);

                        if (! $rightNode)
                        {
                            continue;
                        }
                        if (! $associativity instanceof OxtAssociativityGroupRight &&
                            $rightNode->getPriority() === $priority
                        ) {
                            continue;
                        }
                        if ($associativity instanceof OxtAssociativityStepRight &&
                            ! $rightNode->getType() instanceof OxtOperatorTypeStep
                        ) {
                            continue;
                        }

                        $childNodes->push ($rightNode);
                        unset ($rightNode);
                    }

                    $childNodes->finalize();

                    return new OxtCodeNode(
                        $operator->getType(),   // CodeNodeType
                        null,                   // name
                        null,                   // select
                        $childNodes,            // childnodes
                        null,                   // attributes
                        $priority               // priority
                    );
                }
            }
        }
        
        // операторы не справились с этим выражением.
        return false;
    }
}
