<?php

namespace RemoteStorage;

use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;
use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\HttpResponse as HttpResponse;

use \OAuth\RemoteResourceServer as RemoteResourceServer;

class RemoteStorage
{
    private $_config;
    private $_logger;
    private $_rs;

    public function __construct(Config $c, Logger $l = NULL)
    {
        $this->_config = $c;
        $this->_logger = $l;

        $this->_rs = new RemoteResourceServer($this->_config->getSectionValues("OAuth"));
        $this->_fs = new FileStorage($this->_config, $this->_logger);
    }

    public function handleRequest(HttpRequest $request)
    {
        $response = new HttpResponse(200, "application/json");
        $response->setHeader("Access-Control-Allow-Origin", "*");

#        $this->_rs->verifyRequest();

        $service = $this->_fs; // FIXME: can this be avoided??
        $rs = $this->_rs; // FIXME: can this be avoided??

        try {
            $request->matchRest("OPTIONS", NULL, function() use ($response) {
                $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Origin");
                $response->setHeader("Access-Control-Allow-Methods", "GET, PUT, DELETE");
            });

            ################
            # PUBLIC FILES #
            ################

            // get a file
            $request->matchRest("GET", "/:user/public/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // no auth required
                // FIXME: deal with Content-Type
                $content = $service->getFile($request->getPathInfo());
                if (FALSE === $content) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
                $response->setContent($content);
            });

            // get a directory listing
            $request->matchRest("GET", "/:user/public/:module/:path+/", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $rs->verifyRequest();
                if ($user !== $rs->getResourceOwnerId()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $rs->requireScope("$module:r $module:rw");

                $content = $service->getDir($request->getPathInfo());
                if (FALSE === $content) {
                    throw new RemoteStorageException("not_found", "directory not found");
                }
                $response->setContent(json_encode($content, JSON_FORCE_OBJECT));
            });

            // upload/update a file
            $request->matchRest("PUT", "/:user/public/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $rs->verifyRequest();
                if ($user !== $rs->getResourceOwnerId()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $rs->requireScope("$module:rw");

                // FIXME: deal with Content-Type
                $result = $service->putFile($request->getPathInfo(), $request->getContent());
                if (FALSE === $result) {
                    throw new RemoteStorageException("invalid_request", "unable to store file");
                }
            });

            // delete a file
            $request->matchRest("DELETE", "/:user/public/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $rs->verifyRequest();
                if ($user !== $rs->getResourceOwnerId()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $rs->requireScope("$module:rw");

                $result = $service->deleteFile($request->getPathInfo());
                if (FALSE === $result) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
            });

            ####################
            # NON PUBLIC FILES #
            ####################

            // get a file
            $request->matchRest("GET", "/:user/:path+", function($user, $path) use ($rs, $request, &$response, $service) {
                // auth required
                // FIXME: deal with Content-Type
                $content = $service->getFile($request->getPathInfo());
                if (FALSE === $content) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
                $response->setContent($content);
            });

            // get a directory listing
            $request->matchRest("GET", "/:user/:path+/", function($user, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $content = $service->getDir($request->getPathInfo());
                if (FALSE === $content) {
                    throw new RemoteStorageException("not_found", "directory not found");
                }
                $response->setContent(json_encode($content, JSON_FORCE_OBJECT));
            });

            // upload/update a file
            $request->matchRest("PUT", "/:user/:path+", function($user, $path) use ($rs, $request, &$response, $service) {
                // auth required
                // FIXME: deal with Content-Type
                $result = $service->putFile($request->getPathInfo(), $request->getContent());
                if (FALSE === $result) {
                    throw new RemoteStorageException("invalid_request", "unable to store file");
                }
            });

            // delete a file
            $request->matchRest("DELETE", "/:user/:path+", function($user, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $result = $service->deleteFile($request->getPathInfo());
                if (FALSE === $result) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
            });

            $request->matchRestDefault(function($methodMatch, $patternMatch) use ($request, &$response) {
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
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
            if (NULL !== $this->_logger) {
                $this->_logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
            }
        } catch (RemoteResourceServerException $e) {
            $response = new HttpResponse($e->getResponseCode());
            $response->setHeader("WWW-Authenticate", $e->getAuthenticateHeader());
            $response->setHeader("Content-Type", "application/json");
            $response->setContent($e->getContent());
            if (NULL !== $this->_logger) {
                $this->_logger->logWarn($e->getMessage() . PHP_EOL . $e->getDescription() . PHP_EOL . $request . PHP_EOL . $response);
            }
        }

        return $response;

    }

}
