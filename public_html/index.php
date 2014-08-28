<?php
require_once('includes/setupEnvironment.php');

$application = new Zend_Application(
    $environment,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()->run();