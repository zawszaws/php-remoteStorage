<?php

namespace fkooman\RemoteStorage;

use fkooman\Http\Response;
use fkooman\Json\Json;

class FolderResponse extends Response
{
    public function __construct(Folder $folder)
    {
        parent::__construct(200, $folder->getMimeType());
        $this->setContent(Json::enc($folder->getContent()));
    }
}
