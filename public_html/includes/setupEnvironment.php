<?php

defined('PROJECT_ROOT')
        || define('PROJECT_ROOT', realpath(dirname(dirname(dirname(__FILE__) ))) . '/');

defined('APPLICATION_PATH')
        || define('APPLICATION_PATH', PROJECT_ROOT.'/application');

defined('PUBLIC_PATH')
        || define('PUBLIC_PATH', PROJECT_ROOT.'/public_html');

date_default_timezone_set ('UTC');

set_include_path(implode(PATH_SEPARATOR, array(
    APPLICATION_PATH,
    PROJECT_ROOT.'library',
    get_include_path(),
)));

require_once('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
//$autoloader->registerNamespace('YIF_');

if (!Zend_Registry::isRegistered('ENVIRONMENT_NAME'))
{
    $hostMappings = array(
        'youintaflies.com' => 'production',
        'dev.youintaflies.com' => 'development'
    );

    $server = $_SERVER['SERVER_NAME'];

    foreach ($hostMappings as $host => $env)
    {
        if ($server == $host)
        {
            $environment = $env;
            break;
        }
    }

    if (!isset($environment))
    {
        $environment = 'development';
    }

    Zend_Registry::set('ENVIRONMENT_NAME', $environment);
}
else
{
    $environment = Zend_Registry::get('ENVIRONMENT_NAME');
}
define('ENVIRONMENT_NAME',$environment);
define('WEBSITE_URL','youintaflies.com');

//include_once 'Functions.php';