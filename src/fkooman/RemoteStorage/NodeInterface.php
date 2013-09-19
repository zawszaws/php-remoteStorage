<?php

namespace fkooman\RemoteStorage;

interface NodeInterface
{
    public function setRevisionId($revisionId);
    public function getRevisionId();
    public function getMimeType();
}
