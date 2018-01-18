<?php

require_once dirname(__FILE__) . '/OxtExpression.interface.php';
require_once dirname(__FILE__) . '/OxtSubExpressionList.class.php';
require_once dirname(__FILE__) . '/OxtSubExpression.interface.php';
require_once dirname(__FILE__) . '/OxtFreeZoneList.class.php';
require_once dirname(__FILE__) . '/OxtFreeZone.interface.php';

class OxtExpression implements OxtExpressionInterface
{
    // fields
    
    protected $text;
    protected $textLength;
    protected $subExpressionList;
    protected $freeZoneList;
    
    protected $blank     = true;
    protected $finalized = false;
    
    // OxtExpressionInterface
    
    public function __construct()
    {
        $this->text = '';
        $this->textLength = 0;
        $this->subExpressionList = new OxtSubExpressionList();
        $this->freeZoneList = new OxtFreeZoneList();
    }
    
    // OxtExpressionInterface :: create
    
    public function pushText ($text)
    {
        if ($this->finalized)
        {
            throw new OxtExceptionInternal(
                'Attempt to push a text to a finalized expression',
                OxtExceptionInternal::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->text .= strval($text);
        $this->textLength = strlen ($this->text);
        $this->blank = false;
    }
    
    public function pushSubExpression (OxtSubExpressionInterface $subExpression)
    {
        if ($this->finalized)
        {
            throw new OxtExceptionInternal(
                'Attempt to push a subexpression to a finalized expression',
                OxtExceptionInternal::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->subExpressionList->push ($subExpression);
        $this->blank = false;
    }
    
    public function pushFreeZone (OxtFreeZoneInterface $freeZone)
    {
        if ($this->finalized)
        {
            throw new OxtExceptionInternal(
                'Attempt to push a free zone to a finalized expression',
                OxtExceptionInternal::CODE_PUSH_TO_FINALIZED
            );
        }
        
        $this->freeZoneList->push ($freeZone);
        $this->blank = false;
    }
    
    // OxtExpressionInterface :: finalize
    
    public function finalize()
    {
        $this->subExpressionList->finalize();
        $this->freeZoneList->finalize();
        $this->finalized = true;
    }
    
    // OxtExpressionInterface :: read
    
    public function isEmpty()
    {
        return $this->blank;
    }
    
    /**
     * @return string
    **/
    public function getText()
    {
        return $this->text;
    }
    
    public function getTextLength()
    {
        return $this->textLength;
    }
    
    public function getSubstr($start, $end)
    {
        if ($end < 0)
        {
            $end += $this->textLength - 1;
        }
        return substr ($this->text, $start, $end - $start + 1);
    }
    
    /**
     * @return OxtSubExpressionList
    **/
    public function getSubExpressionList()
    {
        if ($this->finalized)
        {
            return $this->subExpressionList;
            
        }
        return $this->subExpressionList->finalization();
    }
    
    /**
     * @return OxtFreeZoneList
    **/
    public function getFreeZoneList()
    {
        if ($this->finalized)
        {
            return $this->freeZoneList;
        }
        return $this->freeZoneList->finalization();
    }
    
    /**
     * @param   int             $start
     * @param   int             $end    Если указано отрицательное значение, оно отсчитывается с конца выражения.
     * @return  OxtExpression | false
    **/
    public function slice ($start, $end)
    {
        if ($start >= $this->textLength)
        {
            throw new OxtExceptionParse(
                'Unable to get slice (' . $start . ', ' . $end . ') from the text "' . $this->text . '" of length ' . $this->textLength,
                OxtExceptionParse::CODE_BAD_SLICE_INDEX
            );
        }
        
        // Преобразуем отрицательный индекс в реальный.
        if ($end < 0)
        {
            $end += $this->textLength - 1;
        }
        
        // Трим подвыражения для простоты разбора.
        while (preg_match ('/\s/', $this->text[$start]) && $start <= $end)
        {
            ++$start;
        }
        if ($start > $end)
        {
            // Выражение пусто, т.е. не является выражением.
            return false;
        }
        while ($end > 0 && preg_match ('/\s/', $this->text[$end]))
        {
            --$end;
        }
        
        $return = new self();
        $return->pushText (substr ($this->text, $start, $end - $start + 1));
        
        // New SubExpressions = те наши подвыражения, которые лежат между началом и концом.
        foreach ($this->subExpressionList as $subExpression)
        {
            $startIndex = $subExpression->getStartIndex();
            $endIndex   = $subExpression->getEndIndex();
            
            if ($startIndex < $start || $endIndex   > $end)
            {
                continue;
            }
            
            $return->pushSubExpression(new OxtSubExpression(
                $subExpression->getOpenCharacter(), 
                $startIndex - $start, 
                $endIndex   - $start
            ));
        }
        
        // New Freezones = промежутки между новыми подвыражениями.
        $lastIndex = 0;
        foreach ($return->getSubExpressionList() as $subExpression)
        {
            $startIndex = $subExpression->getStartIndex();
            if ($startIndex < $lastIndex)
            {
                continue;
            }
            if ($startIndex > $lastIndex)
            {
                $return->pushFreeZone(new OxtFreeZone($lastIndex, $startIndex - 1));
            }
            $lastIndex = $subExpression->getEndIndex() + 1;
        }
        if ($end - $start >= $lastIndex)
        {
            $return->pushFreeZone(new OxtFreeZone($lastIndex, $end - $start));
        }
        
        $return->finalize();
        
        return $return;
    }

    public function split ($separator = ',')
    {
        // Начало строки работает как разделитель.
        $separators = array (-1);
        
        foreach ($this->freeZoneList as $freeZone)
        {
            $startIndex = $freeZone->getStartIndex();
            $endIndex   = $freeZone->getEndIndex();
            $separatorIndex = strpos (substr ($this->text, $startIndex, $endIndex - $startIndex + 1), $separator);
            if (false !== $separatorIndex)
            {
                $separators[] = $startIndex + $separatorIndex;
            }
        }
        $separatorCount = count($separators);
        if (1 == $separatorCount)
        {
            // Запятых нету, делить нечего.
            return array ($this);
        }
        
        // Добавим конец строки как последний разделитель.
        $separators[] = $this->textLength;
        
        $return = array();
        for ($i = 0; $i < $separatorCount; ++$i)
        {
            $return[] = $this->slice($separators[$i] + 1, $separators[$i + 1] - 1);
        }
        return $return;
    }
}
