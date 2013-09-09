<?php

namespace fkooman\RemoteStorage;

class Folder extends AbstractNode implements NodeInterface
{
    /** @var array */
    private $folderList;

    public function __construct(array $folderList, $revisionId = null)
    {
        parent::__construct($revisionId);
        $this->folderList = $folderList;
    }

    public function getContent()
    {
        return json_encode($this->folderList);
    }

    public function getMimeType()
    {
        return "application/json";
    }
}
