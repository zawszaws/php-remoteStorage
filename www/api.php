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

    if($restInfo->isPublicRequest() && !$request->headerExists("HTTP_AUTHORIZATION")) { 
        // only GET of item is allowed, nothing else
        if($restInfo->isDirectoryRequest()) {
            throw new RemoteStorageException("invalid_request", "not allowed to list contents of public folder");
        }
        // public but not listing, return file if it exists...
        $file = realpath($rootDirectory . $restInfo->getPathInfo());
        if(FALSE === $file || !is_file($file)) {
            throw new RemoteStorageException("not_found", "the file was not found");
        }
        // $mimeType = xattr_get($file, 'mime_type');
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
                    throw new RemoteStorageException("invalid_request", "you are not allowed to access files not belonging to you or to a public directory");
                }

                // verify scope
                $c = $restInfo->getCollection();
                $scope = explode(" ", $token['scope']);
                if(!in_array($c . ":r", $scope) && !in_array($c . ":rw", $scope) && !in_array(":rw", $scope) && !in_array(":r", $scope)) {
                    throw new VerifyException("insufficient_scope", "need read or write scope for this collection");
                }

                if($restInfo->isDirectoryRequest()) {
                    // return directory listing
                    $dir = realpath($rootDirectory . $restInfo->getPathInfo());
                    if(FALSE === $dir || !is_dir($dir)) {
                        throw new RemoteStorageException("not_found", "the directory was not found");
                    }
                    $entries = array();
                    foreach(glob($dir . DIRECTORY_SEPARATOR . "*", GLOB_MARK) as $e) {
                        $entries[basename($e)] = filemtime($e);
                    }
                    $response->setContent(json_encode($entries));
                } else { 
                    // accessing file, return file if it exists...
                    $file = realpath($rootDirectory . $restInfo->getPathInfo());
                    if(FALSE === $file || !is_file($file)) {
                        throw new RemoteStorageException("not_found", "the file was not found");
                    }
                    // $mimeType = xattr_get($file, 'mime_type');
                    $response->setHeader("Content-Type", $mimeType);
                    $response->setContent(file_get_contents($file));
                }
                break;
    
            case "PUT":
                $ro = $restInfo->getResourceOwner();
                if($ro !== $token['resource_owner_id']) {
                    throw new RemoteStorageException("invalid_request", "you are not allowed to write files to a location not belonging to you");
                }

                $userDirectory = $rootDirectory . DIRECTORY_SEPARATOR . $ro;
                createDirectories(array($rootDirectory, $userDirectory));

                // verify scope
                $c = $restInfo->getCollection();
                $scope = explode(" ", $token['scope']);
                if(!in_array($c . ":rw", $scope) && !in_array(":rw", $scope)) {
                        throw new VerifyException("insufficient_scope", "need write scope for this collection");
                }

                if($restInfo->isDirectoryRequest()) {
                    // create the directory
                    $newDirectory = $rootDirectory . $restInfo->getPathInfo();
                    createDirectories(array($newDirectory));
                } else {
                    // upload a file
                    $dir = dirname($rootDirectory . $restInfo->getPathInfo()); 
                    createDirectories(array($dir));
                    if(FALSE === $dir || !is_dir($dir)) {
                        throw new RemoteStorageException("not_found", "the directory '" . $dir . "' was not found");
                    }

                    $file = $rootDirectory . $restInfo->getPathInfo();
                    $contentType = $request->headerExists("Content-Type") ? $request->getHeader("Content-Type") : "text/plain";
                    file_put_contents($file, $request->getContent());
                    // also store the accompanying mime type in the file system extended attribute
                    //xattr_set($file, 'mime_type', $contentType);
                }   
                break;

            case "DELETE":


                break;

            case "OPTIONS":

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
                throw new RemoteStorageException("error", "unable to create directory '" . $d . "'");
            }
        }
    }
}

?>
