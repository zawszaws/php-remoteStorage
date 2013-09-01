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
        $remoteStorage = new RemoteStorage($app['fileStorage'], $tokenIntrospection);

        $pathParser = new PathParser("/" . $entityPath);
        if ($pathParser->getIsDirectory()) {
            $directory = $remoteStorage->getDir($pathParser);
            $ifNonMatch = $request->headers->get("If-None-Match");
            if ($ifNonMatch !== $directory->getEntityTag()) {
                return new JsonResponse($directory->getFlatDirectoryList(), 200, array("ETag" => $directory->getEntityTag()));
            }

            return new Response("", 304, array("ETag" => $directory->getEntityTag()));
        }

        $file = $remoteStorage->getFile($pathParser);
        if ($ifNonMatch !== $file->getEntityTag()) {
            return new Response($file->getContent(), 200, array("ETag" => $file->getEntityTag(), "Content-Type" => $file->getMimeType()));
        }

        return new Response("", 304, array("ETag" => $file->getEntityTag()));
    }

    public function put(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['fileStorage'], $tokenIntrospection);

        return $remoteStorage->putFile(new PathParser("/" . $entityPath), $request->getContent(), $request->getMimeType());
    }

    public function delete(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['fileStorage'], $tokenIntrospection);

        return $remoteStorage->deleteFile(new PathParser("/" . $entityPath));
    }
}
