<?php

namespace fkooman\RemoteStorage;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class RequestHandler
{
    private function introspectToken(Request $request, Application $app)
    {
        $resourceServer = $app['resourceServer'];
        $resourceServer->setAuthorizationHeader($request->headers->get("Authorization"));
        $resourceServer->setAccessTokenQueryParameter($request->get('access_token'));

        return $resourceServer->verifyToken();
    }

    public function get(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['documentStorage'], $tokenIntrospection);
        $responseHeaders = new ResponseHeaders();

        $ifNonMatch = $request->headers->get("If-None-Match");

        $Path = new Path("/" . $entityPath);
        if ($Path->getIsFolder()) {
            $folder = $remoteStorage->getFolder($Path);
            if ($ifNonMatch !== $folder->getEntityTag()) {
                return new JsonResponse(
                    $folder->getFlatFolderList(),
                    200,
                    $responseHeaders->getHeaders($folder, "*")
                );
            }

            return new Response("", 304, $ResponseHeaders->getHeaders($folder, "*"));
        }

        $document = $remoteStorage->getDocument($Path);
        if ($ifNonMatch !== $document->getEntityTag()) {
            return new Response(
                $document->getContent(),
                200,
                $responseHeaders->getHeaders($document, "*")
            );
        }

        return new Response("", 304, $ResponseHeaders->getHeaders($document, "*"));
    }

    public function put(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['documentStorage'], $tokenIntrospection);
        $responseHeaders = new ResponseHeaders();

        $ifNonMatch = $request->headers->get("If-None-Match");
        if ("*" === $ifNonMatch) {
            // FIXME: if the document exists it should fail to perform the put!
        }

        $Path = new Path("/" . $entityPath);

        $remoteStorage->putDocument($Path, $request->getContent(), $request->headers->get('Content-Type'));

        $document = $remoteStorage->getDocument($Path);

        return new Response(
            "",
            200,
            $responseHeaders->getHeaders($document, $request->headers->get('Origin'))
        );
    }

    public function delete(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['documentStorage'], $tokenIntrospection);
        $responseHeaders = new ResponseHeaders();

        $document = $remoteStorage->getDocument($Path);

        $remoteStorage->deleteDocument(new Path("/" . $entityPath));

        return new Response(
            "",
            200,
            $responseHeaders->getHeaders($document, $request->headers->get('Origin'))
        );
    }

    public function options(Request $request, Application $app, $entityPath)
    {
        $responseHeaders = new ResponseHeaders();

        return new Response(
            "",
            200,
            $responseHeaders->getHeaders(null, "*")
        );
    }
}
