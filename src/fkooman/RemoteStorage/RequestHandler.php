<?php

namespace fkooman\RemoteStorage;

use Pimple;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHandler
{
    public function get(Request $request, Pimple $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['storageBackend'], $tokenIntrospection);
        $responseHeaders = new ResponseHeaders();

        $ifNonMatch = $request->headers->get("If-None-Match");

        $path = new Path("/" . $entityPath);
        if ($path->getIsFolder()) {
            $folder = $remoteStorage->getFolder($path);
            if ($ifNonMatch !== $folder->getRevisionId()) {
                return new Response(
                    $folder->getContent(),
                    200,
                    $responseHeaders->getHeaders($folder, "*")
                );
            }

            return new Response("", 304, $responseHeaders->getHeaders($folder, "*"));
        }

        $document = $remoteStorage->getDocument($path);
        if ($ifNonMatch !== $document->getRevisionId()) {
            return new Response(
                $document->getContent(),
                200,
                $responseHeaders->getHeaders($document, "*")
            );
        }

        return new Response("", 304, $responseHeaders->getHeaders($document, "*"));
    }

    public function put(Request $request, Pimple $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['storageBackend'], $tokenIntrospection);
        $responseHeaders = new ResponseHeaders();

        $ifNonMatch = $request->headers->get("If-None-Match");
        if ("*" === $ifNonMatch) {
            // FIXME: if the document exists it should fail to perform the put!
        }

        $path = new Path("/" . $entityPath);

        $remoteStorage->putDocument(
            $path,
            new Document(
                $request->getContent(),
                $request->headers->get('Content-Type')
            )
        );

        $document = $remoteStorage->getDocument($path);

        // FIXME: respones code should be 201?
        return new Response(
            "",
            200,
            $responseHeaders->getHeaders($document, $request->headers->get('Origin'), false)
        );
    }

    public function delete(Request $request, Pimple $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['storageBackend'], $tokenIntrospection);
        $responseHeaders = new ResponseHeaders();

        $document = $remoteStorage->getDocument(new Path("/" . $entityPath));

        $remoteStorage->deleteDocument(new Path("/" . $entityPath));

        return new Response(
            "",
            200,
            $responseHeaders->getHeaders($document, $request->headers->get('Origin'), false)
        );
    }

    public function options(Request $request, Pimple $app, $entityPath)
    {
        $responseHeaders = new ResponseHeaders();

        return new Response(
            "",
            200,
            $responseHeaders->getHeaders(null, "*")
        );
    }

    private function introspectToken(Request $request, Pimple $app)
    {
        $resourceServer = $app['resourceServer'];
        $resourceServer->setAuthorizationHeader($request->headers->get("Authorization"));
        $resourceServer->setAccessTokenQueryParameter($request->get('access_token'));

        // FIXME: validate it is valid before returning it
        return $resourceServer->verifyToken();
    }
}
