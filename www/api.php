<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use RestService\Http\HttpRequest;
use RestService\Http\HttpResponse;
use RestService\Http\IncomingHttpRequest;

use fkooman\Config\Config;

use fkooman\remotestorage\RemoteStorage;
use fkooman\remotestorage\RemoteStorageException;
use fkooman\remotestorage\FileStorage;
use fkooman\remotestorage\JsonMimeHandler;

use Guzzle\Http\Client;
use fkooman\oauth\rs\ResourceServer;
use fkooman\oauth\rs\ResourceServerException;

$request = NULL;
$response = NULL;

try {
    $config = Config::fromIniFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");

    $filesDirectory = $config->getValue("filesDirectory", true);
    $fileStorage = new FileStorage(new JsonMimeHandler($filesDirectory . "/mimedb.json"), $filesDirectory);

    $tokenIntrospectionEndpoint = $config->getSection('OAuth')->getValue('introspectionEndpoint', true);
    $resourceServer = new ResourceServer(new Client($tokenIntrospectionEndpoint));

    $remoteStorage = new RemoteStorage($resourceServer, $fileStorage);
    //$remoteStorage->setUseSendfile($config->getValue('useXSendfile'));

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = $remoteStorage->handleRequest($request);
} catch (RemoteStorageException $e) {
    $response = new HttpResponse($e->getStatusCode());
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
} catch (ResourceServerException $e) {
    $e->setRealm($this->config->getSection("OAuth")->getValue("realm"));
    $response = new HttpResponse($e->getStatusCode());
    $response->setHeader("WWW-Authenticate", $e->getAuthenticateHeader());
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(
        json_encode(
            array(
                "error" => $e->getMessage(),
                "error_description" => $e->getDescription()
            )
        )
    );
} catch (Exception $e) {
    // any other error thrown by any of the modules, assume internal server error
    $response = new HttpResponse();
    $response->setStatusCode(500);
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
}

if (NULL !== $response) {
    $response->sendResponse();
}
