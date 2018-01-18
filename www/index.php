<?php

define ('DOCUMENT_ROOT', dirname(dirname(__FILE__)));
define ('DEBUG_MODE', true);

ini_set ('display_errors', 1);
ini_set ('error_reporting', -1);

require_once DOCUMENT_ROOT . '/application/bootstrap.php';

try
{
    $app = new AcaseXmlApplication();
}
catch (Exception $e)
{
    // TODO: сохранять логи при включенном DEBUG_MODE.
    print 'Не удалось стартовать приложение. Обратитесь к разработчику.';
    die;
}

try
{
    $app->run();
}
catch (Exception $e)
{
    if (DEBUG_MODE)
    {
        // может реально подвесить всё, если трейс содержит большие структуры, особенно в качестве аргументов.
        // print '<pre>error = ' . print_r($e, 1) . '</pre>';
        print '<pre>error = ' . $e->getMessage() . '</pre>';
    }
    else
    {
        print 'Ошибка в работе приложения. Обратитесь к разработчику.';
    }
    die;
}

header($app->getHeader());
print $app->getOutput();
die;
