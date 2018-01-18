<?php
class ConfigLexer
{
    /**
     * Разбирает секретный файл.
     *
     * Он должен быть отформатирован как набор таких строк:
     *     Key = Value
     * Строки, не отвечающие формату, в частности, начинающиеся с небуквенных
     * символов, игнорируются как комментарии.
     * Результат разбора складывается в приватный массив, который при каждом посте
     * автоматически добавляется к запросу.
    **/
    static function parseSecret ($path)
    {
        if (! file_exists ($path))
        {
            throw new Exception ('Не найден файл авторизации');
        }
        $file = fopen ($path, 'rb');
        if (! $file)
        {
            throw new Exception ('Не удаётся открыть файл авторизации');
        }
        $result = array ();
        while (! feof ($file) && $line = fgets ($file, 1024))
        {
            if (preg_match ('~^\s*(\w+)\s*=\s*(\w+)\s*$~i', $line, $matches))
            {
                $result[$matches[1]] = $matches[2];
            }
            
        }
        fclose ($file);
        return $result;
    }
    
    /**
     * Прочитать настройки формы из файла такого вида:
     *      # comment
     *      [FormName]
     *      FieldName = DefaultValue
     *      FieldName
    **/
    static function parseForms ($path)
    {
        if (! file_exists ($path))
        {
            throw new Exception ('Не найдена конфигурация форм');
        }
        $file = fopen ($path, 'r');
        if (! $file)
        {
            throw new Exception ('Не удаётся открыть конфигурацию форм');
        }
        $forms = array ();
        $currentForm = '';
        while (! feof ($file) && $line = fgets ($file, 1024))
        {
            $line = trim ($line);
            if (preg_match ('~^\[\s*(\w+)\s*\]$~i', $line, $matches))
            {
                $currentForm = $matches[1];
                if (! isset ($forms[$currentForm]))
                {
                    $forms[$currentForm] = array ();
                }
            }
            elseif (preg_match ('~^(\w+)\s*=\s*(\w+)$~ui', $line, $matches))
            {
                $forms[$currentForm][$matches[1]] = $matches[2];
            }
            elseif (preg_match ('~^\w+$~ui', $line, $matches))
            {
                $forms[$currentForm][$matches[0]] = '';
            }
            elseif (preg_match ('~^(#|$)~i', $line))
            {
                // Комментарии, игнорируем.
            }
            else
            {
                // Неформатные строки.
                throw new Exception ('Плохая строка в конфигурации форм: ' . $line);
            }
        }
        fclose ($file);
        return $forms;
    }
}
?>
