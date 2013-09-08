<?php

namespace fkooman\RemoteStorage;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class RequestHandler
{
    const ALLOWED_HEADERS = "Authorization, If-None-Match, Content-Type, Origin, ETag";
    const ALLOWED_VERBS = "GET, PUT, DELETE, HEAD, OPTIONS";

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

        $ifNonMatch = $request->headers->get("If-None-Match");

        $pathParser = new PathParser("/" . $entityPath);
        if ($pathParser->getIsDirectory()) {
            $directory = $remoteStorage->getDir($pathParser);
            if ($ifNonMatch !== $directory->getEntityTag()) {
                return new JsonResponse(
                    $directory->getFlatDirectoryList(),
                    200,
                    array(
                        "ETag" => $directory->getEntityTag(),
                        "Access-Control-Allow-Origin" => "*",
                        "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
                        "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS 
                    )
                );
            }

            return new Response("", 304, array("ETag" => $directory->getEntityTag()));
        }

        $file = $remoteStorage->getFile($pathParser);
        if ($ifNonMatch !== $file->getEntityTag()) {
            return new Response(
                $file->getContent(),
                200,
                array(
                    "ETag" => $file->getEntityTag(),
                    "Content-Type" => $file->getMimeType(),
                    "Access-Control-Allow-Origin" => "*",
                    "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
                    "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS
                )
            );
        }

        return new Response("", 304, array("ETag" => $file->getEntityTag()));
    }

    public function put(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['fileStorage'], $tokenIntrospection);

        $pathParser = new PathParser("/" . $entityPath);
        $remoteStorage->putFile($pathParser, $request->getContent(), $request->headers->get('Content-Type'));

        $file = $remoteStorage->getFile($pathParser);

        return new Response(
            "",
            200,
            array(
                "ETag" => $file->getEntityTag(),
                "Access-Control-Allow-Origin" => $request->headers->get('Origin'),
                "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
                "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS
            )
        );
    }

    public function delete(Request $request, Application $app, $entityPath)
    {
        $tokenIntrospection = $this->introspectToken($request, $app);
        $remoteStorage = new RemoteStorage($app['fileStorage'], $tokenIntrospection);

        $file = $remoteStorage->getFile($pathParser);

        $remoteStorage->deleteFile(new PathParser("/" . $entityPath));

        return new Response(
            "",
            200,
            array(
                "ETag" => $file->getEntityTag(),
                "Access-Control-Allow-Origin" => $request->headers->get('Origin'),
                "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
                "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS
            )
        );
    }

    public function options(Request $request, Application $app, $entityPath)
    {
        return new Response(
            "",
            200,
            array(
                "Access-Control-Allow-Origin" => "*",
                "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
                "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS
            )
        );
    }
}
