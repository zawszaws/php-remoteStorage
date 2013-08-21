<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use RestService\Http\HttpRequest;
use RestService\Http\HttpResponse;
use RestService\Http\IncomingHttpRequest;
use RestService\Utils\Config;
use RestService\Utils\Logger;

use Guzzle\Http\Client;

use RemoteStorage\RemoteStorage;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $client = new Client($config->getSectionValue('OAuth', 'introspectionEndpoint'));

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
