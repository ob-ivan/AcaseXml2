<?php

class DataProvider
{
    private $postSender = null;
    private $forms = array ();
    private $cacheEnabled = true;

    public function __construct ($url, $secretPath, $formsPath)
    {
        try
        {
            $this->postSender = new PostSender ($url, ConfigLexer::parseSecret ($secretPath));
        }
        catch (Exception $e)
        {
            throw new Exception ('Не удалось создать соединение: ' . $e->getMessage());
        }
        $this->forms = ConfigLexer::parseForms ($formsPath);
    }

    public function disableCache ()
    {
        $this->cacheEnabled = false;
    }
    
    public function enableCache ()
    {
        $this->cacheEnabled = true;
    }
    
    public function __call ($name, $args)
    {
        if (isset ($this->forms[$name]))
        {
            /**
             * Сформировать поля следующим образом:
             * - если значение поля передано в качестве аргумента, то установить это значение.
             * - если в аргументах поля нет, выставить дефолтное значение из $this->forms.
            **/
            $fields = array ();
            $i = 0;
            $argn = count ($args);
            foreach ($this->forms[$name] as $fieldName => $defaultValue)
            {
                if ($i >= $argn)
                {
                    $fields[$fieldName] = $defaultValue;
                }
                else
                {
                    $fields[$fieldName] = $args[$i];
                }
                $i++;
            }
            // Теперь подумаем, надо ли посылать запрос.
            $key = $name . ':' . serialize ($fields);
            if ($this->cacheEnabled && Cache::exists ($key))
            {
                return Cache::get ($key);
            }
            else
            {
                // Разбираемся с содержимым и многоязычностью.
                // TODO: try-catch
                $responseRu = $this->postSender->genericRequest ($name, $fields, 'ru');
                $responseEn = $this->postSender->genericRequest ($name, $fields, 'en');
                
                // TODO: проверить success
                $response = self::combineRuEn ($responseRu, $responseEn);
                
                Cache::set ($key, $response);
                return $response;
            }
        }
        else
        {
            throw new Exception ('Вызван неизвестный метод: ' . $name);
        }
    }
    
    /**
     * Сливает два ответа. Если их корневой элемент назывался Alice, то на выходе будет:
     *  <Root>
     *      <Alice Language="ru" attributes>...</Alice>
     *      <Alice Language="en" attributes>...</Alice>
     *  </Root>
    **/
    private static function combineRuEn ($responseRu, $responseEn)
    {
        $xmlRu = new SimpleXMLElement ($responseRu);
        $xmlEn = new SimpleXMLElement ($responseEn);
        
        $xmlRu->addAttribute ('Language', 'ru');
        $xmlEn->addAttribute ('Language', 'en');
        
        $result = '<?xml version="1.0" encoding="utf-8"?><Root>';
        $result .= preg_replace ('~^<\?xml.*\?>~iU', '', $xmlRu->asXML());
        $result .= preg_replace ('~^<\?xml.*\?>~iU', '', $xmlEn->asXML());
        $result .= '</Root>';
        
        return $result;
    }
}

?>
