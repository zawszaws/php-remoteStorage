<?php

namespace fkooman\remotestorage;

use fkooman\Config\Config;
use RestService\Http\HttpRequest;
use RestService\Http\HttpResponse;

use fkooman\oauth\rs\ResourceServer;
use fkooman\oauth\rs\ResourceServerException;

use Guzzle\Http\Client;

class RemoteStorage
{
    private $config;
    private $rs;
    private $fs;

    public function __construct(Config $c, Client $client)
    {
        $this->config = $c;
        $this->rs = new ResourceServer($client);
        $this->fs = new FileStorage($this->config);
    }

    public function handleRequest(HttpRequest $request)
    {
        $response = new HttpResponse(200, "application/json");
        $response->setHeader("Access-Control-Allow-Origin", "*");

        $service = $this->fs; // FIXME: can this be avoided??
        $rs = $this->rs; // FIXME: can this be avoided??
        $config = $this->config; // FIXME: can this be avoided??

        try {
            $request->matchRest("OPTIONS", null, function() use ($response) {
                $response->setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, Origin");
                $response->setHeader("Access-Control-Allow-Methods", "GET, PUT, DELETE");
            });

            ################
            # PUBLIC FILES #
            ################

            // get a file
            $request->matchRest("GET", "/:user/public/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service, $config) {
                // no auth required
                $filePath = $service->getFile($request->getPathInfo(), $contentType);
                if (false === $filePath) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
                $response->setContentType($contentType);
                $response->setContentFile($filePath);
                if ($config->getValue('useXSendfile')) {
                    $response->useXSendfile(true);
                }
            });

            // get a directory listing
            $request->matchRest("GET", "/:user/public/:module(/:path+)/", function($user, $module, $path = null) use ($rs, $request, &$response, $service) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), array("$module:r", "$module:rw", "root:r", "root:rw"));

                $content = $service->getDir($request->getPathInfo());
                if (false === $content) {
                    throw new RemoteStorageException("not_found", "directory not found");
                }
                $response->setContent(json_encode($content, JSON_FORCE_OBJECT));
            });

            // upload/update a file
            $request->matchRest("PUT", "/:user/public/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), array("$module:rw", "root:rw"));

                // FIXME: deal with Content-Type
                $result = $service->putFile($request->getPathInfo(), $request->getContent(), $request->getContentType());
                if (false === $result) {
                    throw new RemoteStorageException("invalid_request", "unable to store file");
                }
            });

            // delete a file
            $request->matchRest("DELETE", "/:user/public/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), array("$module:rw", "root:rw"));

                $result = $service->deleteFile($request->getPathInfo());
                if (false === $result) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
            });

            ####################
            # NON PUBLIC FILES #
            ####################

            // FIXME: we have to watch out for the "public" module, if some file was requested not
            // matching above, like for instance /user/public/hello.txt which contains no
            // module, it matches below here with module "public" and file "hello.txt". BAD.

            // get a file
            $request->matchRest("GET", "/:user/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service, $config) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), array("$module:r", "$module:rw", "root:r", "root:rw"));

                $filePath = $service->getFile($request->getPathInfo(), $contentType);
                if (false === $filePath) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
                $response->setContentType($contentType);
                $response->setContentFile($filePath);
                if ($config->getValue('useXSendfile')) {
                    $response->useXSendfile(true);
                }
            });

            // get a directory listing
            $request->matchRest("GET", "/:user/:module(/:path+)/", function($user, $module, $path = null) use ($rs, $request, &$response, $service) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), array("$module:r", "$module:rw", "root:r", "root:rw"));

                $content = $service->getDir($request->getPathInfo());
                if (false === $content) {
                    throw new RemoteStorageException("not_found", "directory not found");
                }
                $response->setContent(json_encode($content, JSON_FORCE_OBJECT));
            });

            // upload/update a file
            $request->matchRest("PUT", "/:user/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), array("$module:rw", "root:rw"));

                // FIXME: deal with Content-Type
                $result = $service->putFile($request->getPathInfo(), $request->getContent(), $request->getContentType());
                if (false === $result) {
                    throw new RemoteStorageException("invalid_request", "unable to store file");
                }
            });

            // delete a file
            $request->matchRest("DELETE", "/:user/:module/:path+", function($user, $module, $path) use ($rs, $request, &$response, $service) {
                // auth required
                $introspect = $rs->verifyRequest($request->getHeader("Authorization"), $request->getQueryParameter("access_token"));
                if ($user !== $introspect->getSub()) {
                    throw new RemoteStorageException("forbidden", "authorized user does not match user in path");
                }
                $this->requireAnyScope($introspect->getScope(), sarray("$module:rw", "root:rw"));

                $result = $service->deleteFile($request->getPathInfo());
                if (false === $result) {
                    throw new RemoteStorageException("not_found", "file not found");
                }
            });

            $request->matchRestDefault(function($methodMatch, $patternMatch) use ($request, &$response) {
                if (in_array($request->getRequestMethod(), $methodMatch)) {
                    if (!$patternMatch) {
                        throw new RemoteStorageException("not_found", "resource not found");
                    }
                } else {
                    $response->setResponseCode(405);
                    $response->setHeader("Allow", implode(", ", $methodMatch));
                }
            });

        } catch (RemoteStorageException $e) {
            $response = new HttpResponse($e->getStatusCode());
            $response->setHeader("Content-Type", "application/json");
            $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
        } catch (ResourceServerException $e) {
            $e->setRealm($this->config->getSection("OAuth")->getValue("realm"));
            $response = new HttpResponse($e->getStatusCode());
            $response->setHeader("WWW-Authenticate", $e->getAuthenticateHeader());
            $response->setHeader("Content-Type", "application/json");
            $response->setContent(
                json_encode(
                    array(
                        "error" => $e->getMessage(),
                        "error_description" => $e->getDescription()
                    )
                )
            );
        }

        return $response;
    }

    /**
     * Just any of the scopes in $requestedScope should be granted then we are
     * fine
     */
    private function requireAnyScope($grantedScope, array $requestedScope)
    {
        $grantedScopeArray = explode(" ", $grantedScope);
        foreach ($requestedScope as $scope) {
            if (in_array($scope, $grantedScopeArray)) {
                return;
            }
        }
        throw new ResourceServerException("insufficient_scope", "no permission for this call with granted scope");
    }
}
