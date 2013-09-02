<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\RemoteStorage\RemoteStorage;
use fkooman\RemoteStorage\FileStorage;
use fkooman\RemoteStorage\JsonMimeHandler;

use fkooman\oauth\rs\ResourceServer;
use fkooman\oauth\rs\ResourceServerException;

use fkooman\Config\Config;

use Guzzle\Http\Client;

use Symfony\Component\HttpFoundation\JsonResponse;

$app = new Silex\Application();
$app['debug'] = true;

$config = Config::fromIniFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");

$app['fileStorage'] = function() use ($config) {
    $filesDirectory = $config->getValue("filesDirectory", true);

    return new FileStorage(new JsonMimeHandler($filesDirectory . "/mimedb.json"), $filesDirectory);
};

$app['resourceServer'] = function() use ($config) {
    $tokenIntrospectionEndpoint = $config->getSection('OAuth')->getValue('introspectionEndpoint', true);

    return new ResourceServer(new Client($tokenIntrospectionEndpoint));
};

$app->get('/{entityPath}', 'fkooman\RemoteStorage\RequestHandler::get')->assert('entityPath', '.*');
$app->put('/{entityPath}', 'fkooman\RemoteStorage\RequestHandler::put')->assert('entityPath', '.*');
$app->delete('/{entityPath}', 'fkooman\RemoteStorage\RequestHandler::delete')->assert('entityPath', '.*');
$app->match('/{entityPath}', 'fkooman\RemoteStorage\RequestHandler::options')->method('OPTIONS')->assert('entityPath', '.*');

$app->error(function (ResourceServerException $e, $code) {
    return new JsonResponse(
        array(
            "error" => $e->getMessage(),
            "error_description" => $e->getDescription(),
            "code" => $e->getStatusCode()
        ),
        $e->getStatusCode(),
        array("WWW-Authenticate" => $e->getAuthenticateHeader())
    );
});

$app->error(function(Exception $e, $code) {
    return new JsonResponse(array("code" => $code, "error" => $e->getMessage()), $code);
});

$app->run();
