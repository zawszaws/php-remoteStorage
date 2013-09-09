<?php

namespace fkooman\RemoteStorage;

class Folder extends Node
{
    private $folderList;

    public function __construct($entityTag, array $folderList)
    {
        parent::__construct($entityTag);
        $this->folderList = $folderList;
    }

    public function getFolderList()
    {
        return $this->folderList;
    }

    public function getFlatFolderList()
    {
        $flatList = array();
        foreach ($this->folderList as $k => $v) {
            $folderList[$k] = $v->getEntityTag();
        }

        return $flatList;
    }
}
