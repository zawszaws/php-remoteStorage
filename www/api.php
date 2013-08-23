<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use RestService\Http\HttpRequest;
use RestService\Http\HttpResponse;
use RestService\Http\IncomingHttpRequest;
use fkooman\Config\Config;
use RestService\Utils\Logger;

use Guzzle\Http\Client;

use fkooman\remotestorage\RemoteStorage;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = Config::fromIniFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $logger = new Logger($config->getSection('Log')->getValue('logLevel'), $config->getValue('serviceName'), $config->getSection('Log')->getValue('logFile'), $config->getSection('Log')->getValue('logMail'));

    $client = new Client($config->getSection('OAuth')->getValue('introspectionEndpoint'));

    $remoteStorage = new RemoteStorage($config, $logger, $client);
    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());
    $response = $remoteStorage->handleRequest($request);

} catch (Exception $e) {
    // any other error thrown by any of the modules, assume internal server error
    $response = new HttpResponse();
    $response->setStatusCode(500);
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
    if (NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
}

if (NULL !== $logger) {
    $logger->logDebug($request);
}
if (NULL !== $logger) {
    $logger->logDebug($response);
}
if (NULL !== $response) {
    $response->sendResponse();
}
