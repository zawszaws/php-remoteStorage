<?php

namespace fkooman\RemoteStorage;

use fkooman\Http\Response;

class OptionsResponse extends Response
{
    public function __construct()
    {
        parent::__construct(200);
        $responseHeaders = new ResponseHeaders();
        $this->setHeaders($responseHeaders->getHeaders(null, "*", false));
    }
}
