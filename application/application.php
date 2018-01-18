<?php

class AcaseXmlApplication
{
    protected $url;
    protected $secretPath;
    protected $formsPath;
    protected $listerPath;
    protected $resultPath;
    protected $compileStyle;
    
    protected $hotelLister;
    protected $templater;
    
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_XML  = 'text/xml';
    
    /**
     * Варианты папки, из которой брать шаблоны.
    **/
    const COMPILE_STYLE_HTML        = 'table';
    const COMPILE_STYLE_TEXT_TAGGED = 'tagtxt';
    const COMPILE_STYLE_CSV         = 'csv';
    
    protected $internalCharset  = 'utf-8'; // тоже надо бы в конфиг.
    protected $outputCharset = 'UTF-8'; // всё в конфиг!
    protected $bom = "\xEF\xBB\xBF";
    protected $contentType;
    protected $header;
    protected $output;
    
    // public //
    
    public function __construct()
    {
        //// Конфигурация для данной задачи.
        
        // TODO: константы директорий по-хорошему должны приходить из конфига, который подаётся на вход конструктора.
        
        $this->url = 'http://www.acase.ru/xml/form.jsp';
        $this->secretPath = CONFIG_DIRECTORY . '/top_secret.txt';
        $this->formsPath  = CONFIG_DIRECTORY . '/forms.txt';
        $this->listerPath = CACHE_DIRECTORY  . '/lister';
        $this->resultPath = RESULT_DIRECTORY;
        $this->compileStyle = self::COMPILE_STYLE_TEXT_TAGGED;
        $this->contentType = self::CONTENT_TYPE_HTML;
    }

    public function run()
    {
        $this->init();
        
        $concatResult = '';
        $getDataTime = 0;
        $compileTime = 0;
        foreach ($this->getTask() as $id => $type)
        {
            $time = microtime(1);
            $data = $this->getData($id);
            $getDataTime += microtime(1) - $time;
            
            $time = microtime(1);
            $output = $this->compile($data);
            $compileTime += microtime(1) - $time;
            
            $concatResult .= $type . "\n" . $output . "\n";
        }
        
        $time = microtime(1);
        $filename = $this->resultPath . '/' . date('Ymd/Ymd-His') . '.txt';
        $dirname = dirname($filename);
        if (! is_dir($dirname))
        {
            mkdir($dirname, 0777, 1);
        }
        file_put_contents($filename, $concatResult);
        $storeTime = microtime(1) - $time;
        
        $this->output = 
            'Время загрузки данных: ' . $getDataTime . ' сек<br/>' .
            'Время компиляции: ' .      $compileTime . ' сек<br/>' .
            'Время записи на диск: ' .  $storeTime . ' сек<br/>' .
            'Сохранён файл: ' .         $filename . '<br/>' .
            'Done.'
        ;
    }
    
    public function getOutput()
    {
        return $this->output;
    }
    
    public function getHeader()
    {
        return 'Content-Type: ' . $this->contentType . '; charset=' . $this->outputCharset;
    }
    
    // protected //
    
    protected function init()
    {
        //// Создание рабочих объектов.

        try
        {
            $this->hotelLister = new HotelLister ($this->url, $this->secretPath, $this->formsPath, $this->listerPath);
        }
        catch (Exception $e)
        {
            throw new Exception('Не удалось создать агрегатор: ' . $e->getMessage());
        }

        TemplaterOxt::$templatesRoot = TEMPLATE_DIRECTORY;
        try
        {
            $this->templater = new TemplaterOxt ($this->compileStyle);
        }
        catch (Exception $e)
        {
            throw new Exception ('Не удалось создать компилятор: ' . $e->getMessage());
        }
    }
    
    /**
     * Возвращает список заданий на обработку.
     *
     *  @return array (
     *      <Hotel Id> => <BE | NBE>,
     *      ...
     *  )
    **/
    protected function getTask()
    {
        // TODO: забирать из CSV-файла.
        return array (
             800131 => 'BE',
            1100258 => 'BE',
             // 500032 => 'BE',
            1000035 => 'BE',
            9900091 => 'BE',
             300125 => 'BE',
             401716 => 'BE',
            9900034 => 'BE',
        );
    }
    
    /**
     * Достаёт один XML-файл с сервера или с жёсткого диска.
     *
     *  @param  int         $id
     *  @return DOMDocument
    **/
    protected function getData ($id)
    {
        $xml = false;
        try
        {
            $xml = $this->hotelLister->storeHotelDescription ($id);
        }
        catch (Exception $e)
        {
            throw new Exception('Не удалось получить описание отеля: ' . $e->getMessage());
        }
        return $xml;
    }
    
    protected function compile ($data)
    {
        if (empty ($data))
        {
            return $this->convert('Нет данных.');
        }
        
        if (isset ($_GET['debug']) && $_GET['debug'] === 'xml')
        {
            $this->contentType = self::CONTENT_TYPE_XML;
            return $this->convert($data);
        }
        
        $result = '';
        try
        {
            $result = $this->convert($this->templater->apply ('HotelDescription', $data));
        }
        catch (Exception $e)
        {
            throw new Exception(
                'Не удалось скомплировать вывод: (' . get_class($e) . ':' . $e->getCode() . ') ' . $e->getMessage(),
                0,
                $e
            );
        }
        if (empty ($result))
        {
            return $this->convert('Вывод пуст.');
        }
        return $result;
    }
    
    protected function convert ($string)
    {
        // return mb_convert_encoding($this->bom . $string, $this->outputCharset, $this->internalCharset);
        $output = '';
        for ($i = 0, $l = mb_strlen($string, $this->internalCharset); $i < $l; ++$i)
        {
            $sub = mb_substr($string, $i, 1, $this->internalCharset);
            if (strlen ($sub) > 1)
            {
                // a multibyte character
                $sub = mb_convert_encoding($sub, 'UCS-2BE', $this->internalCharset);
                $code = dechex ((ord($sub[0]) << 8) + ord($sub[1]));
                while (strlen ($code) < 4)
                {
                    $code = '0' . $code;
                }
                $output .= '<0x' . $code . '>';
            }
            else
            {
                // an ascii character
                $output .= $sub;
            }
        }
        return $output;
    }
}
