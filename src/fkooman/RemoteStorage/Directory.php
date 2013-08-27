<?php

namespace fkooman\RemoteStorage;

class Directory extends Entity
{
    private $directoryList;

    public function __construct($entityTag, array $directoryList)
    {
        parent::__construct($entityTag);
        $this->directoryList = $directoryList;
    }

    public function getDirectoryList()
    {
        return $this->directoryList;
    }

    public function getFlatDirectoryList()
    {
        $flatList = array();
        foreach ($this->directoryList as $k => $v) {
            $flatList[$k] = $v->getEntityTag();
        }

        return $flatList;
    }
}
