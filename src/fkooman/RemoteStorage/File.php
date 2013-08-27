<?php

namespace fkooman\RemoteStorage;

class File extends Entity
{
    private $content;
    private $mimeType;

    public function __construct($entityTag, $fileContent, $mimeType)
    {
        parent::__construct($entityTag);
        $this->content = $fileContent;
        $this->mimeType = $mimeType;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }
}
