<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "_autoload.php";

use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Http\IncomingHttpRequest as IncomingHttpRequest;
use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;

$logger = NULL;
$request = NULL;
$response = NULL;

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $rootDirectory = $config->getValue('filesDirectory');

    $rs = new RemoteResourceServer($config->getSectionValues("OAuth"));
    $rs->verifyRequest();

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $response = new HttpResponse();
    $response->setHeader("Content-Type", "application/json");
    $response->setHeader("Access-Control-Allow-Origin", "*");

    $service = new RemoteStorage($config, $logger);

    $request->matchRest("OPTIONS", NULL, function() use ($response) {
        $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Origin, If-None-Match, If-Match");
        $response->setHeader("Access-Control-Allow-Methods", "GET, PUT, DELETE, HEAD");
    });

    // PUBLIC FILES
    $request->matchRest("GET", "/public/:user/:path+", function($user, $path) use ($response, $service) {
        // no auth required
    });

    $request->matchRest("PUT", "/public/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
    });

    $request->matchRest("POST", "/public/:user/:path+/", function($user, $path) use ($request, $response, $service) {
        // auth required
    });

    $request->matchRest("DELETE", "/public/:user/:path+", function($user, $path) use ($response, $service) {
        // auth required
    });

    // NON PUBLIC FILES
    $request->matchRest("GET", "/:user/:path+", function($user, $path) use ($response, $service) {
        // auth required
    });

    $request->matchRest("PUT", "/:user/:path+", function($user, $path) use ($request, $response, $service) {
        // auth required
    });

    $request->matchRest("POST", "/:user/:path+/", function($user, $path) use ($request, $response, $service) {
        // auth required
    });

    $request->matchRest("DELETE", "/:user/:path+", function($user, $path) use ($response, $service) {
        // auth required
    });

   } elseif ($request->isPublicRequest() && NULL === $request->getHeader("HTTP_AUTHORIZATION")) {
        // only GET and HEAD of item is allowed, nothing else
        if ($request->getRequestMethod() !== 'HEAD' && $request->getRequestMethod() !== 'GET') {
            throw new RemoteStorageException("method_not_allowed", "only GET and HEAD requests allowed for public files");
        }
        if ($request->isDirectoryRequest()) {
            throw new RemoteStorageException("invalid_request", "not allowed to list contents of public folder");
        }
        // public but not listing, return file if it exists...
        $response = $remoteStorage->getFile($request->getPatInfo());
    } elseif (NULL !== $request->getHeader("HTTP_AUTHORIZATION")) {
        // not public or public with Authorization header
        $token = $rs->verify($request->getHeader("HTTP_AUTHORIZATION"));

        // handle API
        $ro = $request->getResourceOwner();
        if ($ro !== $token['resource_owner_id']) {
            throw new RemoteStorageException("forbidden", "storage path belongs to other user");
        }

        switch ($request->getRequestMethod()) {
            case "GET":
            case "HEAD":
                requireScope($request->getCategory(), "r", $token['scope']);
                if ($request->isDirectoryRequest()) {
                    $response = $remoteStorage->getDir($request->getPathInfo());
                } else {
                    $response = $remoteStorage->getFile($request->getPathInfo());
                }
                break;
            case "PUT":
                requireScope($request->getCategory(), "rw", $token['scope']);
                $response = $remoteStorage->putFile($request->getPathInfo());
                break;

            case "DELETE":
                requireScope($request->getCategory(), "rw", $token['scope']);
                $response = $remoteStorage->deleteFile($request->getPathInfo());
                break;
            default:
                throw new RemoteStorageException("method_not_allowed", "unsupported request method");
        }

//    } else {
  //      throw new VerifyException("invalid_token", "no token provided");
    //}

} catch (Exception $e) {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $logger = new Logger($config->getValue('logDirectory') . DIRECTORY_SEPARATOR . "remoteStorage.log");
    switch (get_class($e)) {
        case "VerifyException":
            $response->setStatusCode($e->getResponseCode());
            $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server",error="%s",error_description="%s"', $e->getMessage(), $e->getDescription()));
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription()), JSON_FORCE_OBJECT));
            $logger->logFatal($e->getLogMessage(TRUE));
            break;

        case "RemoteStorageException":
            $response->setStatusCode($e->getResponseCode());
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription()), JSON_FORCE_OBJECT));
            $logger->logFatal($e->getLogMessage(TRUE));
            break;

        default:
            // any other error thrown by any of the modules, assume internal server error
            $response->setStatusCode(500);
            $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage()), JSON_FORCE_OBJECT));

            $msg = 'Message    : ' . $e->getMessage() . PHP_EOL;
            $msg .= 'Trace      : ' . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            $logger->logFatal($msg);
            break;
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
