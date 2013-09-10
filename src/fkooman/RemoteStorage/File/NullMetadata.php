<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\Path;

class NullMetadata implements MetadataInterface
{
    private $metadataDb;

    public function __construct()
    {
        $this->metadataDb = array();
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
    }

    private function incrementParentFoldersRevisionId(Path $path)
    {
        while (false !== $path->getParentFolder()) {
            $path = new Path($path->getParentFolder());
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
}
