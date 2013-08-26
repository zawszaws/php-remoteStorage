<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new Silex\Application();

$pathRegularExpression = '[a-zA-Z0-9%.-_]+';
$pathRegularExpression = '[a-zA-Z0-9%.-_/]+';

$app->get('/{userId}/{filePath}', function ($userId, $filePath) {
    return $userId . PHP_EOL . $filePath . PHP_EOL;
})
->assert('userId', $userIdRegularExpression)
->assert('filePath', $pathRegularExpression);

$app->run();
