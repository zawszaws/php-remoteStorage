<?php

namespace fkooman\RemoteStorage;

interface NodeInterface
{
    public function getRevisionId();
    public function getMimeType();
}
