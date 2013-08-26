<?php

namespace fkooman\remotestorage;

class NullMimeHandler implements MimeHandlerInterface
{
    private $mimeDb;

    public function __construct()
    {
        $this->mimeDb = array();
    }

    public function setMimeType($filePath, $mimeType)
    {
        $this->mimeDb[$filePath] = $mimeType;
    }

    public function getMimeType($filePath)
    {
        if (!isset($this->mimeDb[$filePath])) {
            throw new MimeHandlerException("unable to get mime type for this file");
        }

        return $this->mimeDb[$filePath];
    }
}
