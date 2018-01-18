<?php

// инициализация ядра.

define ('INTERFACE_DIRECTORY',  DOCUMENT_ROOT . '/interfaces');
define ('MODULE_DIRECTORY',     DOCUMENT_ROOT . '/classes');
define ('CONFIG_DIRECTORY',     DOCUMENT_ROOT . '/config');
define ('TEMPLATE_DIRECTORY',   DOCUMENT_ROOT . '/templates');
define ('CACHE_DIRECTORY',      DOCUMENT_ROOT . '/cache');
define ('RESULT_DIRECTORY',     DOCUMENT_ROOT . '/result');

require_once MODULE_DIRECTORY  . '/ErrorException.php';
require_once MODULE_DIRECTORY  . '/Autoload.php';
require_once dirname(__FILE__) . '/application.php';
