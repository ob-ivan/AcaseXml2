<?php

function __autoload ($className)
{
    if (false !== ($pos = strpos ($className, 'Interface')))
    {
        $interfaceName = substr ($className, 0, $pos);
        $path = INTERFACE_DIRECTORY . '/' . $interfaceName . '.php';
        if (! file_exists ($path))
        {
            throw new Exception ('Нет файла для интерфейса: ' . $className);
        }
        require_once $path;
        if (! interface_exists ($className))
        {
            throw new Exception ('Интерфейс не описан в файле: ' . $className);
        }
        return;
    }
    
    $path = MODULE_DIRECTORY . '/' . $className . '.php';
    if (! file_exists ($path))
    {
        throw new Exception ('Нет файла для класса: ' . $className);
    }
    require_once $path;
    if (! class_exists ($className))
    {
        throw new Exception ('Класс не описан в файле: ' . $className);
    }
}

?>
