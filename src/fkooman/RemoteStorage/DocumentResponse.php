<?php

namespace fkooman\RemoteStorage;

use fkooman\Http\Response;

class DocumentResponse extends Response
{
    public function __construct(Document $document)
    {
        parent::__construct(200, $document->getMimeType());
        $responseHeaders = new ResponseHeaders();
        $this->setContent($document->getContent());
        $this->setHeaders($responseHeaders->getHeaders($document, "*", true));
    }
}
