<?php

namespace fkooman\remotestorage;

class Entity
{
    protected $entityTag;

    public function __construct($entityTag)
    {
        $this->entityTag = $entityTag;
    }

    public function getEntityTag()
    {
        return $this->entityTag;
    }
}
