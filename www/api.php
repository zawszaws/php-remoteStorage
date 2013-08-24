<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use RestService\Http\HttpRequest;
use RestService\Http\HttpResponse;
use RestService\Http\IncomingHttpRequest;
use fkooman\Config\Config;

use Guzzle\Http\Client;
use fkooman\remotestorage\RemoteStorage;

$request = NULL;
$response = NULL;

try {
    $config = Config::fromIniFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");

    $client = new Client($config->getSection('OAuth')->getValue('introspectionEndpoint'));

    $remoteStorage = new RemoteStorage($config, $client);
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = $remoteStorage->handleRequest($request);

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
