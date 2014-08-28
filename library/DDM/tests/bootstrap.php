<?php

define('APPLICATION_ENVIRONMENT', 'testing');

defined('PROJECT_ROOT')
    || define('PROJECT_ROOT', realpath(dirname(dirname(dirname(__FILE__)))) . '/');

// Ensure framework is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(PROJECT_ROOT),
    realpath(PROJECT_ROOT . 'zend/library/'),
    get_include_path(),
)));

require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('DDM_');
$loader->suppressNotFoundWarnings(false);
$loader->setFallbackAutoloader(true);
