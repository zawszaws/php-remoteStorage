<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Node;

class NullMetadata implements MetadataInterface
{
    private $metadataDb;

    public function __construct()
    {
        $this->metadataDb = array();
    }

    public function setMetadata(Path $path, Node $node)
    {
        $this->metadataDb[$nodePath] = array(
            'mimeType' => $node->getMimeType(),
            'revisionId' => $node->getRevisionId() + 1
        );
    }

    public function getMetadata(Path $path)
    {
        if (!isset($this->metadataDb[$nodePath])) {
            throw new MetadataException("unable to get metadata for this node");
        }

        return new Node(
            $this->metadataDb[$nodePath]['mimeType'],
            $this->metadataDb[$nodePath]['revisionId']
        );
    }
}
