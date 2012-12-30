<?php

require_once 'SplClassLoader.php';

$c1 = new SplClassLoader("RestService", dirname(__DIR__) . DIRECTORY_SEPARATOR . "extlib" . DIRECTORY_SEPARATOR . "php-rest-service" . DIRECTORY_SEPARATOR . "lib");
$c1->register();
$c2 = new SplClassLoader("OAuth", dirname(__DIR__) . DIRECTORY_SEPARATOR . "extlib" . DIRECTORY_SEPARATOR . "php-lib-remote-rs" . DIRECTORY_SEPARATOR . "lib");
$c2->register();
$c2 = new SplClassLoader("RemoteStorage", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c2->register();
