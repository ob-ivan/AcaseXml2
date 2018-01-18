<?php

// TODO: Хранить в CodeNode информацию о файле, строке, позиции, тексте, из которых он получен.
// Очень пригодится при ловле эксепшнов.

require_once dirname(__FILE__) . '/OxtParse.interface.php';
require_once dirname(__FILE__) . '/OxtCodeBlock.class.php';
require_once dirname(__FILE__) . '/OxtCodeNode.class.php';
require_once dirname(__FILE__) . '/OxtParseExpression.class.php';

class OxtParse implements OxtParseInterface
{
    // OxtParseInterface //

    public static function parseInput($input)
    {
        $code = new OxtCodeBlock();
        $variableNames = array();
        $templateNames = array();
        $prevLength = -1;
        if (preg_match ('/^\xef\xbb\xbf/', $input))
        {
            // utf-8 bom prefix
            $input = substr ($input, 3);
        }
        $input = trim ($input);
        while (! empty ($input))
        {
            $curLength = strlen ($input);
            $debugInput = substr ($input, 0, 100) . '...';
            if ($prevLength == $curLength)
            {
                throw new OxtExceptionParse(
                    'Unrecognizable token at: "' . $debugInput . '"',
                    OxtExceptionParse::CODE_BAD_TOKEN
                );
            }

            $node = self::parseTerm ($input, true);
            if (! $node && empty ($input))
            {
                break;
            }
            if (! $node instanceof OxtCodeNodeInterface)
            {
                throw new OxtInternalException (
                    'parseTerm returned not a code node without exception at "' . $debugInput . '"',
                    OxtInternalException::CODE_UNEXPECTED_RETURN_TYPE
                );
            }

            $nodeType = $node->getType();
            if (! (
                $nodeType instanceof OxtCodeNodeTypeTemplate ||
                $nodeType instanceof OxtCodeNodeTypeVariable
            )) {
                throw new OxtExceptionParse(
                    'Only &templates and $constants are allowed at top-level',
                    OxtExceptionParse::CODE_BAD_TOP_LEVEL_TERM
                );
            }
            if ($nodeType instanceof OxtCodeNodeTypeTemplate)
            {
                if (isset ($templateNames[$node->getText()]))
                {
                    throw new OxtExceptionParse(
                        'Duplicate template name "' . $node->getText() . '"',
                        OxtExceptionParse::CODE_DUPLICATE_NAME
                    );
                }
                $templateNames[$node->getText()] = 1;
            }
            if ($nodeType instanceof OxtCodeNodeTypeVariable)
            {
                if (isset ($variableNames[$node->getText()]))
                {
                    throw new OxtExceptionParse(
                        'Duplicate constant name "' . $node->getText() . '"',
                        OxtExceptionParse::CODE_DUPLICATE_NAME
                    );
                }
                $variableNames[$node->getText()] = 1;
            }
            unset ($nodeType);

            $code->push ($node);
            $prevLength = $curLength;
        }

        $code->finalize();
        return $code;
    }

    // protected //

    /**
     *  @return OxtCodeNode | false
    **/
    public static function parseTerm (&$input, $topLevel)
    {
        $input = self::ltrim($input);

        // при topLevel = true -- определение шаблона,
        // при topLevel = false -- вызов.
        if ($input[0] == '&')
        {
            $input = self::ltrim (substr ($input, 1));
            preg_match ('/\w*/', $input, $matches);
            $name = $matches[0];
            $input = self::ltrim (substr ($input, strlen ($name)));
            $argumentNodes = new OxtCodeBlock();
            while (! empty ($input) && $input[0] == '@')
            {
                $newArgument = self::parseAttribute($input);
                $argumentNodes->push($newArgument);
            }
            $argumentNodes->finalize();
            $childNodes = self::parseDefinition($input);
            if ($topLevel)
            {
                $type = new OxtCodeNodeTypeTemplate;
            }
            else
            {
                $type = new OxtCodeNodeTypeCallTemplate;
            }
            return new OxtCodeNode(
                $type,
                $name,
                null,   // select
                $childNodes,
                $argumentNodes
            );
        }

        // объявление переменной. имя переменной должно следовать за долларом непосредственно.
        if ($input[0] == '$')
        {
            $input = substr ($input, 1);
            preg_match ('/^[\w-]*/', $input, $matches);
            $name = $matches[0];
            $input = self::ltrim (substr ($input, strlen ($name)));
            $childNodes = self::parseDefinition($input);
            return new OxtCodeNode(
                new OxtCodeNodeTypeVariable,
                $name,
                null,   // select
                $childNodes
            );
        }

        // одиноко стоящий атрибут.
        if ($input[0] == '@')
        {
            return self::parseAttribute ($input);
        }

        // инлайновый стиль.
        if ($input[0] == '^')
        {
            throw new OxtExceptionParse(
                'Inline styles are not implemented yet',
                OxtExceptionParse::CODE_NOT_IMPLEMENTED
            );
        }

        // служебное слово.
        if ($input[0] == '!')
        {
            $input = substr ($input, 1);
            preg_match ('/\w*/', $input, $matches);
            $name = $matches[0];
            $input = self::ltrim (substr ($input, strlen ($name)));
            $select = null;
            $nodeType = null;
            if (preg_match ('/^(if|wh(en|ile)|for)$/i', $name))
            {
                if ($input[0] !== '{')
                {
                    throw new OxtExceptionParse(
                        'Control word "' . $name . '" must be followed by an {expression}',
                        OxtExceptionParse::CODE_MISSING_EXPRESSION
                    );
                }
                $select = self::parseExpression ($input);
            }
            switch (strtolower($name))
            {
                case 'if'       : $nodeType = new OxtCodeNodeTypeIf;        break;
                case 'when'     : $nodeType = new OxtCodeNodeTypeWhen;      break;
                case 'while'    : $nodeType = new OxtCodeNodeTypeWhile;     break;
                case 'for'      : $nodeType = new OxtCodeNodeTypeFor;       break;
                case 'choose'   : $nodeType = new OxtCodeNodeTypeChoose;    break;
                case 'otherwise': $nodeType = new OxtCodeNodeTypeOtherwise; break;
                default:
                {
                    throw new OxtExceptionParse(
                        'Unknown control word: "' . $name . '"',
                        OxtExceptionParse::CODE_UNKNOWN_CONTROL
                    );
                }
            }
            $childNodes = self::parseDefinition($input);
            return new OxtCodeNode(
                $nodeType,
                null, // text
                $select,
                $childNodes
            );
        }

        // строка.
        if ($input[0] == '"' || $input[0] == '\'')
        {
            return self::parseString ($input);
        }

        // выражение.
        if ($input[0] == '{')
        {
            return self::parseExpression ($input);
        }

        // html-тэг.
        if ($input[0] == '%')
        {
            throw new OxtExceptionParse(
                'Html tags are not implemented yet',
                OxtExceptionParse::CODE_NOT_IMPLEMENTED
            );
        }

        // число.
        if (preg_match('/^\d+/', $input, $matches))
        {
            $value = $matches[0];
            $input = substr ($input, strlen ($value));
            return new OxtCodeNode(
                new OxtCodeNodeTypeNumber,
                $value
            );
        }

        // WTF
        throw new OxtExceptionParse(
            'Unparseable term: "' . substr($input, 0, 100) . '..."',
            OxtExceptionParse::CODE_BAD_TOKEN
        );
    }

    /**
     * Содержимое нода -- его чайлды. Может быть либо заключено в группирующие скобки, либо быть выражением,
     * либо за знаком равенства строкой в кавычках или одним словом без кавычек.
    **/
    protected static function parseDefinition (&$input)
    {
        $input = self::ltrim ($input);
        $equal = false;
        $children = new OxtCodeBlock();

        // Запомним для будущего разбора, был ли знак равенства.
        if ($input[0] == '=')
        {
            $input = self::ltrim (substr ($input, 1));
            $equal = true;
        }

        // Чайлды в скобках.
        if ($input[0] == '(')
        {
            $input = self::ltrim (substr ($input, 1));
            $prevLength = -1;
            while (! empty ($input) && $input[0] != ')')
            {
                $curLength = strlen ($input);
                if ($prevLength == $curLength)
                {
                    throw new OxtExceptionParse(
                        'Unrecognizable token at: "' . substr($input, 0, 100) . '..."',
                        OxtExceptionParse::CODE_BAD_TOKEN
                    );
                }
                $prevInput = $input;
                $node = self::parseTerm ($input, false);
                if ($node)
                {
                    $children->push($node);
                }
                else
                {
                    throw new OxtExceptionParse(
                        'Could not parse term at: "' . substr($prevInput, 0, 100) . '..."',
                        OxtExceptionParse::CODE_BAD_TOKEN
                    );
                }
                unset ($node);
                $prevLength = $curLength;
                $input = self::ltrim ($input);
            }
            if (empty ($input))
            {
                throw new OxtExceptionParse(
                    'Missing closing parenthesis',
                    OxtExceptionParse::CODE_MISSING_CLOSING_PARENTHESIS
                );
            }
            // assert: $input[0] === ')'. Eat it.
            $input = self::ltrim (substr ($input, 1));
        }
        // Выражение.
        elseif ($input[0] == '{')
        {
            $node = self::parseExpression($input);
            if ($node)
            {
                $children->push($node);
            }
        }
        // За знаком равенства может быть строка в скобках или без.
        elseif ($equal)
        {
            if ($input[0] == '"' || $input[0] == '\'')
            {
                $node = self::parseString($input);
            }
            else
            {
                // Слово без кавычек может включать в себя цифры, подчёркивания, дефисы, двоеточия (?!).
                preg_match ('/^[\w:-]*/u', $input, $matches);
                $value = $matches[0];
                $input = self::ltrim (substr ($input, strlen ($value)));
                $node = new OxtCodeNode(
                    new OxtCodeNodeTypeString,
                    $value
                );
            }
            if ($node)
            {
                $children->push($node);
            }
        }
        $children->finalize();
        return $children;
    }

    protected static function parseString (&$input)
    {
        $input = self::ltrim ($input);
        if (! ($input[0] == '"' || $input[0] == '\''))
        {
            return false;
        }
        $quote = $input[0];
        $value = '';
        for ($i = 1, $l = strlen ($input); $i < $l; ++$i)
        {
            if ($input[$i] == $quote)
            {
                break;
            }
            if ($input[$i] == '\\')
            {
                // Допускается экранирование текущих кавычек и бэкслешей.
                ++$i;
                if ($input[$i] == $quote || $input[$i] == '\\')
                {
                    $value .= $input[$i];
                    continue;
                }
                /**
                 * В двойных кавычках допускаются коды:
                 *  \r      0x0a
                 *  \n      0x0d
                 *  \t      0x09
                **/
                if ($quote == '"')
                {
                    if ($input[$i] == 'r')
                    {
                        $value .= "\r";
                        continue;
                    }
                    if ($input[$i] == 'n')
                    {
                        $value .= "\n";
                        continue;
                    }
                    if ($input[$i] == 't')
                    {
                        $value .= "\t";
                        continue;
                    }
                }
                // Если мы здесь, то бэкслеш был не по делу. Следовательно, его нужно вставить.
                $value .= '\\';
            }
            $value .= $input[$i];
        }
        $input = self::ltrim (substr ($input, $i + 1));
        return new OxtCodeNode(
            new OxtCodeNodeTypeString,
            $value
        );
    }

    /**
     * @return OxtCodeNode | null
    **/
    protected static function parseExpression (&$input)
    {
        // Временное решение, пока используется стандартный xpath.
        // return self::parseExpressionPrimitive ($input);

        // Самостоятельный разбор выражений.
        return OxtParseExpression::parseExpression ($input);
    }

    /**
     * Примитивное восприятие выражений как текста.
     *
     *  @param  &string             $input - разобранное выражение убирается с головы входа.
     *  @return OxtCodeNode | null
    **/
    protected static function parseExpressionPrimitive (&$input)
    {
        $input = self::ltrim ($input);
        if ($input[0] != '{')
        {
            return null;
        }
        $quote = $input[0];
        $value = '';
        for ($i = 1, $l = strlen ($input); $i < $l; ++$i)
        {
            if ($input[$i] == '}')
            {
                break;
            }
            if ($input[$i] == '\\')
            {
                // Допускается экранирование закрывающей скобки.
                ++$i;
                if ($input[$i] == '}')
                {
                    $value .= $input[$i];
                    continue;
                }
                // Если мы здесь, то бэкслеш был не по делу. Следовательно, его нужно вставить.
                $value .= '\\';
            }
            $value .= $input[$i];
        }
        $input = self::ltrim (substr ($input, $i + 1));
        return new OxtCodeNode(
            new OxtCodeNodeTypeExpression,
            $value
        );
    }

    /**
     * Выполняет функцию ltrim'а, походу пожирая комментарии и бесполезные символы.
    **/
    protected static function ltrim ($input)
    {
        $input = ltrim ($input);

        if (empty ($input))
        {
            return '';
        }

        // запятые и точки с запятой -- чисто визуальные, никакого значения они не несут. игнорируем.
        if (preg_match ('/[,;]/', $input[0]))
        {
            $input = ltrim (substr ($input, 1));
        }

        if (empty ($input))
        {
            return '';
        }

        // комментарии.
        while (preg_match ('/--|\[\[/', substr ($input, 0, 2)))
        {
            if ($input[0] == '-')
            {
                $n = strpos ($input, "\n");
                if ($n === false)
                {
                    $n = strpos ($input, "\r");
                }
                if ($n === false)
                {
                    // файл закончился строчным комментарием.
                    return '';
                }
                $input = ltrim (substr ($input, $n + 1));
            }
            else
            {
                $n = strpos ($input, ']]', 2);
                if ($n === false)
                {
                    // файл закончился незакрытым блочным комментарием.
                    return '';
                }
                $input = ltrim (substr ($input, $n + 2));
            }
        }

        return $input;
    }

    /**
     *  @return OxtCodeNode | null
    **/
    protected static function parseAttribute (&$input)
    {
        $input = self::ltrim ($input);
        if ($input[0] != '@')
        {
            return false;
        }
        $input = self::ltrim (substr ($input, 1));

        if (! preg_match ('/^[\w:-]*/', $input, $matches))
        {
            return false;
        }
        $name = $matches[0];
        $input = self::ltrim (substr ($input, strlen ($name)));
        if (! empty ($input) && $input[0] == '=')
        {
            $childNodes = self::parseDefinition($input);
            return new OxtCodeNode(
                new OxtCodeNodeTypeAttribute,
                $name,
                null,   // select
                $childNodes
            );
        }
        return new OxtCodeNode(
            new OxtCodeNodeTypeAttribute,
            $name
        );
    }

}
