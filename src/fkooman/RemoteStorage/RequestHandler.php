<?php

namespace fkooman\RemoteStorage;

use fkooman\Http\Service;
use fkooman\Http\Request;

use fkooman\OAuth\ResourceServer\ResourceServer;

class RequestHandler
{
    private $storageBackend;
    private $resourceServer;
    private $tokenSet;

    public function __construct(StorageInterface $storageBackend, ResourceServer $resourceServer)
    {
        $this->storageBackend = $storageBackend;
        $this->resourceServer = $resourceServer;
        $this->tokenSet = false;
    }

    public function handleRequest(Request $request)
    {
        $service = new Service($request);

        // if any authorization information is available, set it here
        if(!$this->tokenSet) {
            $this->resourceServer->setAuthorizationHeader($request->getHeader("Authorization"));
            $this->resourceServer->setAccessTokenQueryParameter($request->getQueryParameter("access_token"));
            $this->tokenSet = true;
        }

        $remoteStorage = new RemoteStorage($this->storageBackend, $this->resourceServer);

        $service->match(
            "GET",
            "/:pathInfo+/",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new FolderResponse(
                    $remoteStorage->getFolder(
                        new Path($request->getPathInfo()),
                        $request->getHeader("If-None-Match")
                    )
                );
            }
        );

        $service->match(
            "GET",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new DocumentResponse(
                    $remoteStorage->getDocument(
                        new Path($request->getPathInfo()),
                        $request->getHeader("If-None-Match")
                    )
                );
            }
        );

        $service->match(
            "PUT",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new DocumentResponse(
                    $remoteStorage->putDocument(
                        new Path($request->getPathInfo()),
                        new Document(
                            $request->getContent(),
                            $request->getHeader("Content-Type")
                        ),
                        $request->getHeader("If-Match"),
                        $request->getHeader("If-None-Match")
                    )
                );
            }
        );

        $service->match(
            "DELETE",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new DocumentResponse(
                    $remoteStorage->deleteDocument(
                        new Path($request->getPathInfo()),
                        $request->getHeader("If-Match")
                    )
                );
            }
        );

        // FIXME: make wildcard also match directories
        $service->match(
            "OPTIONS",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new OptionsResponse();
            }
        );

        // FIXME: make wildcard also match directories
        $service->match(
            "OPTIONS",
            "/:pathInfo+/",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new OptionsResponse();
            }
        );

        return $service->run();
    }
}
