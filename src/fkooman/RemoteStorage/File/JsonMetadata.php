<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\Path;

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

    public function setMetadata(Path $path, $mimeType, $revisionId = 1)
    {
        if (!is_string($mimeType) || 0 >= strlen($mimeType)) {
            throw new MetadataException("mime type must be non-empty string");
        }
        $this->metadataDb[$path->getPath()] = array(
            'mimeType' => $mimeType,
            'revisionId' => $revisionId
        );
        $this->incrementParentFoldersRevisionId($path);
        $this->writeJsonFile();
    }

    public function getMetadata(Path $path)
    {
        if (!isset($this->metadataDb[$path->getPath()])) {
            throw new MetadataException(sprintf("unable to get metadata for '%s' node", $path->getPath()));
        }

        return array(
            "mimeType" => $this->metadataDb[$path->getPath()]['mimeType'],
            "revisionId" => $this->metadataDb[$path->getPath()]['revisionId']
        );
    }

    private function incrementParentFoldersRevisionId(Path $path)
    {
        while (false !== $path->getParentPath()) {
            $path = new Path($path->getParentPath());
            $this->incrementFolderRevisionId($path);
        }
    }

    private function incrementFolderRevisionId(Path $path)
    {
        if (!$path->getIsFolder()) {
            throw new MetadataException("not a folder");
        }
        if (isset($this->metadataDb[$path->getPath()])) {
            $this->metadataDb[$path->getPath()]['revisionId'] = $this->metadataDb[$path->getPath()]['revisionId'] + 1;
        } else {
            $this->metadataDb[$path->getPath()] = array(
                'mimeType' => "application/json",
                'revisionId' => 1
            );
        }
    }

    private function writeJsonFile()
    {
        if (false === @file_put_contents($this->metadataFile, json_encode($this->metadataDb))) {
            throw new MetadataException("unable to set mime type for this node");
        }
    }
}
