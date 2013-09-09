<?php

namespace fkooman\RemoteStorage;

class ResponseHeaders
{
    const ALLOWED_VERBS = "GET, PUT, DELETE";
    const ALLOWED_HEADERS = "Authorization, If-None-Match, Content-Type, Origin, ETag";

    public function getHeaders(Node $node = null, $requestOrigin = null)
    {
        $response = array(
            "Access-Control-Allow-Origin" => (null === $requestOrigin) ? "*" : $requestOrigin,
            "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
            "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS
        );

        // if a node is set include the entityTag as well
        if (null !== $node) {
            $response["ETag"] = strval($node->getRevisionId());
        }

        // if the node is a document, also add the Content-Type
        if ($node instanceof Document) {
            $response['Content-Type'] = $node->getMimeType();
        }

        // if the node is a folder, also add the "application/json" Content-Type
        if ($node instanceof Folder) {
            $response['Content-Type'] = "application/json";
        }

        return $response;
    }
}
