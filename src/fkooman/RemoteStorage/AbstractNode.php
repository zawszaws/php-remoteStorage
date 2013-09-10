<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Exception\NodeException;

abstract class AbstractNode implements NodeInterface
{
    /** @var int */
    private $revisionId;

    public function __construct($revisionId = null)
    {
        if (null === $revisionId) {
            $this->revisionId = 1;
        } else {
            if (!is_int($revisionId) || 0 >= $revisionId) {
                throw new NodeException("revision id must be positive integer");
            }
            $this->revisionId = $revisionId;
        }
    }

    public function getRevisionId()
    {
        return $this->revisionId;
    }
}
