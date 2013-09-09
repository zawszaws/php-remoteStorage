<?php

namespace fkooman\RemoteStorage;

abstract class AbstractNode implements NodeInterface
{
    /** @var int */
    private $revisionId;

    public function __construct($revisionId = null)
    {
        if (null !== $revisionId) {
            $this->revisionId = $revisionId;
        } else {
            $this->revisionId = 1;
        }
    }

    public function getRevisionId()
    {
        return $this->revisionId;
    }
}
