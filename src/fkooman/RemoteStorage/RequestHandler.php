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

        $pathParser = new PathParser("/" . $entityPath);
        if ($pathParser->getIsFolder()) {
            $folder = $remoteStorage->getFolder($pathParser);
            if ($ifNonMatch !== $folder->getEntityTag()) {
                return new JsonResponse(
                    $folder->getFlatFolderList(),
                    200,
                    $responseHeaders->getHeaders($folder, "*")
                );
            }

            return new Response("", 304, $ResponseHeaders->getHeaders($folder, "*"));
        }

        $document = $remoteStorage->getDocument($pathParser);
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

        $pathParser = new PathParser("/" . $entityPath);

        $remoteStorage->putDocument($pathParser, $request->getContent(), $request->headers->get('Content-Type'));

        $document = $remoteStorage->getDocument($pathParser);

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

        $document = $remoteStorage->getDocument($pathParser);

        $remoteStorage->deleteDocument(new PathParser("/" . $entityPath));

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
