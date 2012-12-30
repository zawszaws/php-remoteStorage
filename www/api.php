<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "_autoload.php";

use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Http\IncomingHttpRequest as IncomingHttpRequest;
use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;

use \OAuth\RemoteResourceServerException as RemoteResourceServerException;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $rootDirectory = $config->getValue('filesDirectory');

    $rs = new RemoteResourceServer($config->getSectionValues("OAuth"));
    //$rs->verifyRequest();

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $response = new HttpResponse(200, "application/json");
    $response->setHeader("Access-Control-Allow-Origin", "*");

    $service = new RemoteStorage($config, $logger);

    $request->matchRest("OPTIONS", NULL, function() use ($response) {
        $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Origin, If-None-Match, If-Match");
        $response->setHeader("Access-Control-Allow-Methods", "GET, PUT, DELETE");
    });

    ################
    # PUBLIC FILES #
    ################

    // get a file
    $request->matchRest("GET", "/public/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // no auth required
        $response = $service->getFile($request->getPathInfo());
    });

    // get a directory listing
    $request->matchRest("GET", "/public/:user/:path+/", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->getDir($request->getPathInfo());
    });

    // upload/update a file
    $request->matchRest("PUT", "/public/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->putFile($request->getPathInfo(), $request->getContent(), $request->getHeader("Content-Type"));
    });

    // delete a file
    $request->matchRest("DELETE", "/public/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->deleteFile($request->getPathInfo());
    });

    ####################
    # NON PUBLIC FILES #
    ####################

    // get a file
    $request->matchRest("GET", "/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->getFile($request->getPathInfo());
    });

    // get a directory listing
    $request->matchRest("GET", "/:user/:path+/", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->getDir($request->getPathInfo());
    });

    // upload/update a file
    $request->matchRest("PUT", "/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->putFile($request->getPathInfo(), $request->getContent(), $request->getHeader("Content-Type"));
    });

    // delete a file
    $request->matchRest("DELETE", "/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
        $response = $service->deleteFile($request->getPathInfo());
    });

    $request->matchRestDefault(function($methodMatch, $patternMatch) use ($request) {
        if (in_array($request->getRequestMethod(), $methodMatch)) {
            if (!$patternMatch) {
                throw new ProxyException("not_found", "resource not found");
            }
        } else {
            $response->setResponseCode(405);
            $response->setHeader("Allow", implode(", ", $methodMatch));
        }
    });

} catch (RemoteStorageException $e) {
    $response = new HttpResponse($e->getResponseCode());
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getMessage())));
    if (NULL !== $logger) {
        $logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (RemoteResourceServerException $e) {
    $response = new HttpResponse($e->getResponseCode());
    $response->setHeader("WWW-Authenticate", $e->getAuthenticateHeader());
    $response->setHeader("Content-Type", "application/json");
    $response->setContent($e->getContent());
    if (NULL !== $logger) {
        $logger->logWarn($e->getMessage() . PHP_EOL . $e->getDescription() . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (Exception $e) {
    $response = new HttpResponse(500);
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
