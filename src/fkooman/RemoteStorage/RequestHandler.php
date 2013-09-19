<?php

namespace fkooman\RemoteStorage;

use fkooman\Http\Request;

use fkooman\OAuth\ResouceServer\ResourceServer;

class RequestHandler
{
    private $storageBackend;
    private $resourceServer;

    public function __construct(StorageInterface $storageBackend, ResourceServer $resourceServer)
    {
        $this->storageBackend = $storageBackend;
        $this->resourceServer = $resourceServer;
    }

    public function handleRequest(Request $request)
    {
        // if any authorization information is available, set it here
        $this->resourceServer->setAuthorizationHeader($request->getHeader("Authorization"));
        $this->resourceServer->setAccessTokenQueryParameter($request->getQueryParameter("access_token"));

        $remoteStorage = new RemoteStorage($this->storageBackend, $this->resourceServer);

        $request->matchRest(
            "GET",
            "/:pathInfo+/",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new FolderResponse(
                    $remoteStorage->getFolder(
                        $pathInfo,
                        $request->getHeader("If-None-Match")
                    )
                );
            }
        );

        $request->matchRest(
            "GET",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return new DocumentResponse(
                    $remoteStorage->getDocument(
                        $pathInfo,
                        $request->getHeader("If-None-Match")
                    )
                );
            }
        );

        $request->matchRest(
            "PUT",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return $remoteStorage->putDocument(
                    $pathInfo,
                    new Document(
                        $request->getContent(),
                        $request->getHeader("Content-Type")
                    ),
                    $request->getHeader("If-Match"),
                    $request->getHeader("If-None-Match")
                );
            }
        );

        $request->matchRest(
            "DELETE",
            "/:pathInfo+",
            function ($pathInfo) use ($request, $remoteStorage) {
                return $remoteStorage->deleteDocument(
                    $pathInfo,
                    $request->getHeader("If-Match")
                );
            }
        );

        // FIXME: does this also match directory?
        $request->matchRest(
            "OPTIONS",
            "/:pathInfo+",
            function ($pathInfo) use ($remoteStorage) {
                return $remoteStorage->optionsDocument($pathInfo);
            }
        );

        // FIXME: default match
    }
}
