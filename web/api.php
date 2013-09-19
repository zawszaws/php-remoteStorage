<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\RemoteStorage\RemoteStorage;
use fkooman\RemoteStorage\File\FileStorage;
use fkooman\RemoteStorage\File\JsonMetadata;

use fkooman\Config\Config;

use fkooman\Http\IncomingRequest;
use fkooman\Http\Request;

use Guzzle\Http\Client;

try {
    $config = Config::fromIniFile(dirname(__DIR__) . "/config/remoteStorage.ini");

    $fileStorage = new FileStorage(
        new JsonMetadata(
            $config->getValue("filesDirectory", true) . "/mimedb.json",
            $config->getValue("filesDirectory", true)
        )
    );

    $resourceServer = new ResourceServer(
        new Client(
            $config->getSection('OAuth')->getValue('introspectionEndpoint', true)
        )
    );

    $request = Request::fromIncomingRequest(new IncomingRequest());

    $requestHandler = new RequestHander($fileStorage, $resourceServer);
    $response = $requestHandler->handleRequest($request);

} catch (RemoteStorageException $e) {
    // when there is a problem with the remoteStorage call
    $response = new Response($e->getStatusCode(), "application/json");
    $response->setContent(
        Json::enc(
            array(
                "error" => $e->getMessage(),
                "error_description" => $e->getDescription(),
                "code" => $e->getStatusCode()
            )
        )
    );
} catch (ResourceServerException $e) {
    // when there is a problem with the OAuth authorization
    $response = new Response($e->getStatusCode(), "application/json");
    $response->setHeader("WWW-Authenticate", $e->getAuthenticateHeader());
    $response->setContent(
        Json::enc(
            array(
                "error" => $e->getMessage(),
                "error_description" => $e->getDescription(),
                "code" => $e->getStatusCode()
            )
        )
    );
} catch (Exception $e) {
    // in all other cases...
    $response = new Response(500, "application/json");
    $response->setContent(
        Json::enc(
            array(
                "code" => 500,
                "error" => "internal_server_error",
                "error_description" => $e->getMessage()
            )
        )
    );
}

if (null === $response) {
    // FIXME: what if null?!
    $response->sendResponse();
}
