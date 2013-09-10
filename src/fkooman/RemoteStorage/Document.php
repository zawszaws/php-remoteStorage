<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Exception\NodeException;

class Document extends AbstractNode implements NodeInterface
{
    /** @var string */
    private $content;

    /** @var string */
    private $mimeType;

    public function __construct($content, $mimeType, $revisionId = null)
    {
        parent::__construct($revisionId);
        $this->content = $content;

        if (!is_string($mimeType) || 0 >= strlen($mimeType)) {
            throw new NodeException("mime type must be non-empty string");
        }
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
