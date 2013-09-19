<?php

namespace fkooman\RemoteStorage;

use fkooman\Json\Json;

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
        return Json::enc($this->folderList);
    }

    public function getMimeType()
    {
        return "application/json";
    }
}
