<?php

require_once "../lib/Config.php";
require_once "../lib/Logger.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";
require_once "../lib/OAuth/RemoteResourceServer.php";
require_once "../lib/Storage/RemoteStorage.php";
require_once "../lib/Storage/RemoteStorageRequest.php";
require_once "../lib/Storage/RemoteStorageException.php";

$remoteStorageVersion = "remoteStorage.2012.10";

$response = new HttpResponse();

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $rootDirectory = $config->getValue('filesDirectory');
    $request = RemoteStorageRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $rs = new RemoteResourceServer($config->getValue("oauthTokenEndpoint"));

    $remoteStorage = new RemoteStorage($config, $request);

    if("OPTIONS" === $request->getRequestMethod()) {
        $response->setHeader("Access-Control-Allow-Origin", "*");
        $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Origin, If-None-Match, If-Match");
        $response->setHeader("Access-Control-Allow-Methods", "GET, PUT, DELETE, HEAD");
    } else if($request->isPublicRequest() && NULL === $request->getHeader("HTTP_AUTHORIZATION")) { 
        // only GET and HEAD of item is allowed, nothing else
        if ($request->getRequestMethod() !== 'HEAD' && $request->getRequestMethod() !== 'GET') {
            throw new RemoteStorageException("method_not_allowed", "only GET and HEAD requests allowed for public files");
        }
        if($request->isDirectoryRequest()) {
            throw new RemoteStorageException("invalid_request", "not allowed to list contents of public folder");
        }
        // public but not listing, return file if it exists...
        $response = $remoteStorage->getFile($request->getPatInfo());
    } else if (NULL !== $request->getHeader("HTTP_AUTHORIZATION")) {
        // not public or public with Authorization header
        $token = $rs->verify($request->getHeader("HTTP_AUTHORIZATION"));

        // handle API
        $ro = $request->getResourceOwner();
        if($ro !== $token['resource_owner_id']) {
            throw new RemoteStorageException("forbidden", "storage path belongs to other user");
        }

        switch($request->getRequestMethod()) {
            case "GET":
            case "HEAD":
                requireScope($request->getCategory(), "r", $token['scope']);
                if($request->isDirectoryRequest()) {
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

    } else {
        throw new VerifyException("invalid_token", "no token provided");
    }   
} catch (Exception $e) {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");
    $logger = new Logger($config->getValue('logDirectory') . DIRECTORY_SEPARATOR . "remoteStorage.log");
    switch(get_class($e)) {
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

$response->setHeader("Access-Control-Allow-Origin", "*");
$response->setHeader("X-RemoteStorage-Version", $remoteStorageVersion);
$response->sendResponse();

// FIXME: move this to RemoteResourceServer class!
function requireScope($collection, $permission, $grantedScope) {
    if(!in_array($permission, array("r", "rw"))) {
        throw new Exception("unsupported permission requested");
    }
    $g = explode(" ", $grantedScope);

    if("r" === $permission) {
        // both "r" and "rw" are okay here
        if(!in_array($collection . ":r", $g) && !in_array($collection . ":rw", $g) && !in_array(":r", $g) && !in_array(":rw", $g)) {
            throw new VerifyException("insufficient_scope", "require read permissions for this operation [" . $collection . "," . $permission . "," . $grantedScope . "]");
        }
    } else {
        // only "rw" is okay here
        if(!in_array($collection . ":rw", $g) && !in_array(":rw", $g)) {
            throw new VerifyException("insufficient_scope", "require write permissions for this operation [" . $collection . "," . $permission . "," . $grantedScope . "]");
        }
    }
}

?>
