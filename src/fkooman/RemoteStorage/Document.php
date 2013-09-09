<?php

namespace fkooman\RemoteStorage;

class Document extends AbstractNode implements NodeInterface
{
    private $content;
    private $mimeType;

    public function __construct($content, $mimeType, $revisionId = null)
    {
        parent::__construct($revisionId);
        $this->content = $content;
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
