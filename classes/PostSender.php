<?php

/**
 * Класс запрашивания данных у одного партнёра.
 *
 * У нас будет ровно один URL.
 * Кроме того, партнёр предоставляет данные по авторизации, которые надо включать в каждый запрос. Храним их.
 *
**/
class PostSender
{
    private $partnerURL = '';
    private $authFields = array ();
    
    /**
     * Начальная инициализация объекта включает в себя проверку,
     * что партнёр доступен, сохранение URL и данных авторизации,
     * сбор информации о формах, которые можно посылать партнёру.
     *
     * @param   $partnerURL string  URL в правильном формате
     * @param   $authFields array   пары ключ-значения, которые надо добавить в пост-данные, чтобы партнёр обработал запрос.
     * @param   $formsPath  array   путь
    **/
    public function __construct ($partnerURL, $authFields)
    {
        // Проверяем входные данные.
        if (! parse_url ($partnerURL))
        {
            throw new Exception ('Плохой URL: ' . $partnerURL);
        }
        if (! is_array ($authFields))
        {
            throw new Exception ('authFields должен быть массивом');
        }
        
        $this->partnerURL = $partnerURL;
        
        // Пока что пустой запрос без авторизации, потому что хотя бы ответ "access denied" я хотел бы услышать.
        $response = $this->postForm (false, false);
        if (empty ($response))
        {
            throw new Exception ('Сервер не отвечает: ' . $partnerURL);
        }
        
        // Уложим (возможно) разветвлённую структуру в плоский массив. - нет у нас пока такой функции
        // $this->authFields = \Utils::postSerializeFields ($authFields);
        $this->authFields = $authFields;
    }
    
    /**
     * Выполняет собственно POST-запрос.
     * 
     * Данные должны быть уже целиком подготовлены.
     * Авторизация добавляется перед постом автоматически,
     * если выставлен соответствующий флаг.
     * Возвращает тело ответа, если он получен, false иначе.
     *
     * @param   $fields     array       пост-данные
     * @param   $authorize  bool        добавлять ли поля авторизации
     * @return              bool|string содержание ответа или false, если ответа не было.
    **/
    private function postForm ($fields, $authorize = true)
    {
        // Будем считать, что никаких данных вызывающая функция передать не захотела.
        if (! is_array ($fields)) $fields = false;
        
        $curl = curl_init ($this->partnerURL);
        if (! $curl)
        {
            throw new Exception ('cURL не смог установить соединение с ' . $this->partnerURL);
        }
        curl_setopt ($curl, CURLOPT_POST, true);
        if ($fields)
        {
            if ($authorize)
            {
                /**
                 * Если на входе данные, перекрывающие авторизацию, значит,
                 * вызывающая функция этого хотела. Добавим только то, чего нет.
                **/
                $fields += $this->authFields;
            }
            if (! curl_setopt ($curl, CURLOPT_POSTFIELDS, $fields))
            {
                throw new Exception ('Не удалось назначить поля POST');
            }
        }
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($curl);
        curl_close ($curl);
        
        return $result;
    }
    
    public function genericRequest ($RequestName, $fields, $Language)
    {
        return $this->postForm (array (
            'RequestName' => $RequestName,
        ) + $fields + array (
            'Language'    => $Language,
        ));
    }
}

?>
