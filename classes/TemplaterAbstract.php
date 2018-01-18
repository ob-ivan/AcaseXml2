<?php

abstract class TemplaterAbstract implements TemplaterInterface
{
    static $templatesRoot = '';

    protected $styleName = '';
    protected $templatesDirectory = '';
    
    /**
     * Назначаем папки и проверяем их наличие.
    **/
    public function __construct ($styleName)
    {
        if (empty (self::$templatesRoot))
        {
            throw new Exception (
                'Перед инстанциацией ' . __CLASS__ . 
                ' надо установить значение переменной ' . __CLASS__ . '::$templatesRoot'
            );
        }
        if (! (file_exists (self::$templatesRoot) && is_dir (self::$templatesRoot)))
        {
            throw new Exception ('Корневая папка с шаблонами должна быть реально существующей папкой');
        }
        $templatesDirectory = self::$templatesRoot . '/' . $styleName;
        if (! (file_exists ($templatesDirectory) && is_dir ($templatesDirectory)))
        {
            throw new Exception ('Папка с шаблонами для стиля ' . $styleName . ' должна быть реально существующей папкой');
        }
        $this->templatesDirectory = $templatesDirectory;
        $this->styleName = $styleName;
    }
}
