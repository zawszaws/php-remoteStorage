<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\RemoteStorage\RemoteStorage;
use fkooman\RemoteStorage\PathParser;
use fkooman\RemoteStorage\FileStorage;
use fkooman\RemoteStorage\JsonMimeHandler;

use fkooman\oauth\rs\ResourceServer;
use fkooman\oauth\rs\ResourceServerException;

// next line goes away
use fkooman\oauth\rs\TokenIntrospection;

use fkooman\Config\Config;

use Guzzle\Http\Client;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

$app = new Silex\Application();
$app['debug'] = true;

$config = Config::fromIniFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");

$filesDirectory = $config->getValue("filesDirectory", true);
$fileStorage = new FileStorage(new JsonMimeHandler($filesDirectory . "/mimedb.json"), $filesDirectory);

$tokenIntrospectionEndpoint = $config->getSection('OAuth')->getValue('introspectionEndpoint', true);
$resourceServer = new ResourceServer(new Client($tokenIntrospectionEndpoint));

$app->get('/{entityPath}', function (Request $request, $entityPath) use ($resourceServer, $fileStorage) {
    //$tokenIntrospection = $resourceServer->verifyRequest($request->headers->get("Authorization"), $request->get('access_token'));
    $tokenIntrospection = new TokenIntrospection(
        array(
            "active" => true,
            "sub" => "admin",
            "scope" => "music:r music:rw"
        )
    );

    $remoteStorage = new RemoteStorage($fileStorage, $tokenIntrospection);

    $pathParser = new PathParser("/" . $entityPath);
    if ($pathParser->getIsDirectory()) {
        $directory = $remoteStorage->getDir($pathParser);

        return new JsonResponse($directory->getFlatDirectoryList(), 200, array("ETag" => $directory->getEntityTag()));
    }

    $file = $remoteStorage->getFile($pathParser);

    return new Response($file->getContent(), 200, array("ETag" => $file->getEntityTag()));

})->assert('entityPath', '.*');

$app->put('/{entityPath}', function (Request $request, $entityPath) use ($resourceServer, $fileStorage) {
    $tokenIntrospection = $resourceServer->verifyRequest($request->headers->get("Authorization"), $request->get('access_token'));
    $remoteStorage = new RemoteStorage($fileStorage, $tokenIntrospection);

    return $remoteStorage->putFile(new PathParser("/" . $entityPath), $request->getContent(), $request->getMimeType());
})->assert('entityPath', '.*');

$app->delete('/{entityPath}', function (Request $request, $entityPath) use ($resourceServer, $fileStorage) {
    $tokenIntrospection = $resourceServer->verifyRequest($request->headers->get("Authorization"), $request->get('access_token'));
    $remoteStorage = new RemoteStorage($fileStorage, $tokenIntrospection);

    return $remoteStorage->deleteFile(new PathParser("/" . $entityPath));
})->assert('entityPath', '.*');

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
