<?php

namespace fkooman\RemoteStorage\File;

class JsonMetadata implements MetadataInterface
{
    private $metadataFile;
    private $metadataDb;

    public function __construct($metadataFile)
    {
        $this->metadataFile = $metadataFile;
        $metadataFileContent = @file_get_contents($metadataFile);
        if (false === $metadataFileContent) {
            // no database yet, not a problem
            $this->metadataDb = array();
        } else {
            $this->metadataDb = json_decode($metadataFileContent, true);
        }
    }

    public function getMetadata($nodePath)
    {
        if (!array_key_exists($nodePath, $this->metadataFileContent)) {
            throw new MetadataException("unable to get metadata for this node");
        }

        return new Node($this->metadataFileContent[$nodePath]);
    }

    public function setMimeType($filePath, $mimeType)
    {
        $this->metadataFileContent[$filePath] = $mimeType;
        if (false === @file_put_contents($this->mimeDbFile, json_encode($this->mimeDb))) {
            throw new MimeHandlerException("unable to set mime type for this file");
        }
    }

    public function incrementRevision($filePath)
    {

    }

    public function getRevision($filePath)
    {

    }
}
