<?php

namespace fkooman\remotestorage;

class JsonMimeHandler implements MimeHandlerInterface
{
    private $mimeDbFile;
    private $mimeDb;

    public function __construct($mimeDbFile)
    {
        $this->mimeDbFile = $mimeDbFile;
        $fileContent = @file_get_contents($mimeDbFile);
        if (false === $fileContent) {
            // no database yet, not a problem
            $this->mimeDb = array();
        } else {
            $this->mimeDb = json_decode($fileContent, true);
        }
    }

    public function getMimeType($filePath)
    {
        if (!array_key_exists($filePath, $this->mimeDb)) {
            throw new MimeHandlerException("unable to get mime type for this file");
        }

        return $this->mimeDb[$filePath];
    }

    public function setMimeType($filePath, $mimeType)
    {
        $this->mimeDb[$filePath] = $mimeType;
        if (false === @file_put_contents($this->mimeDbFile, json_encode($this->mimeDb))) {
            throw new MimeHandlerException("unable to set mime type for this file");
        }
    }
}
