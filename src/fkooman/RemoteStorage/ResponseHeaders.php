<?php

namespace fkooman\RemoteStorage;

class ResponseHeaders
{
    const ALLOWED_VERBS = "GET, PUT, DELETE";
    const ALLOWED_HEADERS = "Authorization, If-None-Match, Content-Type, Origin, ETag";

    public function getHeaders(NodeInterface $node = null, $requestOrigin = null, $addContentType = true)
    {
        $response = array(
            "Access-Control-Allow-Origin" => (null === $requestOrigin) ? "*" : $requestOrigin,
            "Access-Control-Allow-Methods" => self::ALLOWED_VERBS,
            "Access-Control-Allow-Headers" => self::ALLOWED_HEADERS,
        );

        // if a node is set include the entityTag as well
        if (null !== $node) {
            $response["ETag"] = strval($node->getRevisionId());
            if ($addContentType) {
                $response['Content-Type'] = $node->getMimeType();
            }
        }

        return $response;
    }
}
