<?php

namespace fkooman\RemoteStorage;

class Folder extends AbstractNode implements NodeInterface
{
    private $folderList;

    public function __construct(array $folderList, $revisionId = null)
    {
        parent::__construct($revisionId);
        $this->folderList = $folderList;
    }

    public function getFolderList()
    {
        return $this->folderList;
    }

    public function getFlatFolderList()
    {
        $flatList = array();
        foreach ($this->folderList as $name => $node) {
            $flatList[$name] = $node->getRevisionId();
        }

        return $flatList;
    }

    public function getMimeType()
    {
        return "application/json";
    }
}
