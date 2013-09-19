<?php

namespace fkooman\RemoteStorage;

use fkooman\Http\Response;

class FolderResponse extends Response
{
    public function __construct(Folder $folder)
    {
        parent::__construct(200, $folder->getMimeType());
        $responseHeaders = new ResponseHeaders();
        $this->setContent($folder->getContent());
        $this->setHeaders($responseHeaders->getHeaders($folder, "*", true));
    }
}
