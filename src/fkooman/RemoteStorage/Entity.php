<?php

namespace fkooman\RemoteStorage;

class Entity
{
    private $entityTag;

    public function __construct($entityTag)
    {
        $this->entityTag = strval($entityTag);
    }

    public function getEntityTag()
    {
        return $this->entityTag;
    }
}
