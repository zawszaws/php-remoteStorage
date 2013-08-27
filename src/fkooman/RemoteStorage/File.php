<?php

namespace fkooman\RemoteStorage;

class File extends Entity
{
    private $fileContent;
    private $mimeType;

    public function __construct($entityTag, $fileContent, $mimeType)
    {
        parent::__construct($entityTag);
        $this->fileContent = $fileContent;
        $this->mimeType = $mimeType;
    }

    public function getFileContent()
    {
        return $this->fileContent;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }
}
