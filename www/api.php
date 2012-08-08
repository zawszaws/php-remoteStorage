<?php

require_once "../lib/Config.php";
require_once "../lib/Http/Uri.php";
require_once "../lib/Http/HttpRequest.php";
require_once "../lib/Http/HttpResponse.php";
require_once "../lib/Http/IncomingHttpRequest.php";
require_once "../lib/OAuth/RemoteResourceServer.php";
require_once "../lib/Storage/RemoteStorageRestInfo.php";
require_once "../lib/Storage/RemoteStorageException.php";

$response = new HttpResponse();
$response->setHeader("Content-Type", "application/json");
$response->setHeader("Access-Control-Allow-Origin", "*");

try { 
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini");

    $rootDirectory = $config->getValue('filesDirectory');

    $incomingRequest = new IncomingHttpRequest();
    $request = $incomingRequest->getRequest();

    $restInfo = new RemoteStorageRestInfo($request->getPathInfo(), $request->getRequestMethod());
    
    $rs = new RemoteResourceServer($config->getValue("oauthTokenEndpoint"));

    if("OPTIONS" === $restInfo->getRequestMethod()) {
        $response->setHeader("Access-Control-Allow-Origin", "*");
        $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Origin");
        $response->setHeader("Access-Control-Allow-Methods", "GET, PUT, DELETE");
    } else if($restInfo->isPublicRequest() && !$request->headerExists("HTTP_AUTHORIZATION")) { 
        // only GET of item is allowed, nothing else
        if($restInfo->isDirectoryRequest()) {
            throw new RemoteStorageException("invalid_request", "not allowed to list contents of public folder");
        }
        // public but not listing, return file if it exists...
        $file = realpath($rootDirectory . $restInfo->getPathInfo());
        if(FALSE === $file || !is_file($file)) {
            throw new RemoteStorageException("not_found", "file not found");
        }
        if(function_exists("xattr_get")) {
            $mimeType = xattr_get($file, 'mime_type');
        } else {
            $mimeType = "application/json";
        }
        $response->setHeader("Content-Type", $mimeType);
        $response->setContent(file_get_contents($file));
    } else if ($request->headerExists("HTTP_AUTHORIZATION")) {
        // not public or public with Authorization header
        $token = $rs->verify($request->getHeader("HTTP_AUTHORIZATION"));

        // handle API
        switch($restInfo->getRequestMethod()) {
            case "GET":
                $ro = $restInfo->getResourceOwner();
                if($ro !== $token['resource_owner_id']) {
                    throw new RemoteStorageException("access_denied", "storage path belongs to other user");
                }

                requireScope($restInfo->getCollection(), "r", $token['scope']);

                if($restInfo->isDirectoryRequest()) {
                    // return directory listing
                    $dir = realpath($rootDirectory . $restInfo->getPathInfo());
                    $entries = array();
                    if(FALSE !== $dir && is_dir($dir)) {
                        foreach(glob($dir . DIRECTORY_SEPARATOR . "*", GLOB_MARK) as $e) {
                            $entries[basename($e)] = filemtime($e);
                        }
                    }
                    $response->setContent(json_encode($entries));
                } else { 
                    // accessing file, return file if it exists...
                    $file = realpath($rootDirectory . $restInfo->getPathInfo());
                    if(FALSE === $file || !is_file($file)) {
                        throw new RemoteStorageException("not_found", "file not found");
                    }
                    if(function_exists("xattr_get")) {
                        $mimeType = xattr_get($file, 'mime_type');
                    } else {
                        $mimeType = "application/json";
                    }
                    $response->setHeader("Content-Type", $mimeType);
                    $response->setContent(file_get_contents($file));
                }
                break;
    
            case "PUT":
                $ro = $restInfo->getResourceOwner();
                if($ro !== $token['resource_owner_id']) {
                    throw new RemoteStorageException("access_denied", "storage path belongs to other user");
                }

                $userDirectory = $rootDirectory . DIRECTORY_SEPARATOR . $ro;
                // FIXME: only create when it does not already exists...
                createDirectories(array($rootDirectory, $userDirectory));

                requireScope($restInfo->getCollection(), "rw", $token['scope']);

                if($restInfo->isDirectoryRequest()) {
                    throw new RemoteStorageException("invalid_request", "cannot store a directory");
                } 

                // upload a file
                $file = $rootDirectory . $restInfo->getPathInfo();
                $dir = dirname($file);
                if(FALSE === realpath($dir)) {
                    createDirectories(array($dir));
                }
                $contentType = $request->headerExists("Content-Type") ? $request->getHeader("Content-Type") : "application/json";
                file_put_contents($file, $request->getContent());
                // store mime_type
                if(function_exists("xattr_set")) {
                    xattr_set($file, 'mime_type', $contentType);
                }

                break;

            case "DELETE":
                $ro = $restInfo->getResourceOwner();
                if($ro !== $token['resource_owner_id']) {
                    throw new RemoteStorageException("access_denied", "storage path belongs to other user");
                }

                $userDirectory = $rootDirectory . DIRECTORY_SEPARATOR . $ro;

                requireScope($restInfo->getCollection(), "rw", $token['scope']);

                if($restInfo->isDirectoryRequest()) {
                    throw new RemoteStorageException("invalid_request", "directories cannot be deleted");
                }

                $file = $rootDirectory . $restInfo->getPathInfo();            
                if(!file_exists($file)) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
                if(!is_file($file)) {
                    throw new RemoteStorageException("invalid_request", "object is not a file");
                }
                if (@unlink($file) === FALSE) {
                    throw new Exception("unable to delete file");
                }
                break;
            default:
                // ...
                break;

        }

    } else {
        $response->setStatusCode(401);
        $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server"'));
        $response->setContent(json_encode(array("error"=> "not_authorized", "error_description" => "need authorization to access this service")));
    }   
} catch (Exception $e) {
    switch(get_class($e)) {
        case "VerifyException":
            $response->setStatusCode($e->getResponseCode());
            $response->setHeader("WWW-Authenticate", sprintf('Bearer realm="Resource Server",error="%s",error_description="%s"', $e->getMessage(), $e->getDescription()));
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            break;

        case "RemoteStorageException":
            $response->setStatusCode($e->getResponseCode());
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            break;

        default:
            // any other error thrown by any of the modules, assume internal server error
            $response->setStatusCode(500);
            $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
            break;
    }

}

$response->sendResponse();

function createDirectories(array $directories) { 
    foreach($directories as $d) { 
        if(!file_exists($d)) {
            if (@mkdir($d, 0775, TRUE) === FALSE) {
                throw new Exception("unable to create directory");
            }
        }
    }
}

function requireScope($collection, $permission, $grantedScope) {
    if(!in_array($permission, array("r", "rw"))) {
        throw new Exception("unsupported permission requested");
    }
    $g = explode(" ", $grantedScope);
    if(NULL === $collection) {
        if(!in_array(":" . $permission, $g)) {
            throw new VerifyException("insufficient_scope", "insufficient permissions for this operation [" . $collection . "," . $permission . "," . $grantedScope . "]");
        }
    } else {
        if(!in_array($collection . ":" . $permission, $g) && !in_array(":" . $permission, $g)) {
            throw new VerifyException("insufficient_scope", "insufficient permissions for this operation [" . $collection . "," . $permission . "," . $grantedScope . "]");
        }
    }
}

?>
