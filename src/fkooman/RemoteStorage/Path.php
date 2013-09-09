<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Exception\PathException;

class Path
{
    private $userId;
    private $isPublic;
    private $moduleName;
    private $isFolder;
    private $isModuleRoot;
    private $path;

    public function __construct($path)
    {
        if (!is_string($path)) {
            throw new PathException("path MUST be a string");
        }
        if (0 !== strpos($path, "/")) {
            throw new PathException("path MUST start with a '/'");
        }

        $entityParts = explode("/", $path);
        $partCount = count($entityParts);
        if (4 > $partCount) {
            throw new PathException("path MUST include user and category folder");
        }
        $this->isModuleRoot = 4 === $partCount;

        if ("public" === $entityParts[2]) {
            // if public, the entityParts need to contain an extra as "public" does not count then
            if (5 > $partCount) {
                throw new PathException("public path MUST include user and category folder");
            }
            $this->isModuleRoot = 5 === $partCount;
        }

        // path parts cannot be empty, except the last one
        for ($i = 1; $i < $partCount-1; $i++) {
            if (0 >= strlen($entityParts[$i])) {
                throw new PathException("path part cannot be empty");
            }
        }

        $this->userId = $entityParts[1];
        $this->isPublic = "public" === $entityParts[2];
        $this->moduleName = ($this->isPublic) ? $entityParts[3] : $entityParts[2];
        $this->isFolder = empty($entityParts[count($entityParts)-1]);
        $this->path = $path;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getIsPublic()
    {
        return $this->isPublic;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getIsFolder()
    {
        return $this->isFolder;
    }

    public function getIsDocument()
    {
        return !$this->isFolder;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getParentPath()
    {
        if ($this->isModuleRoot) {
            return false;
        }

        return dirname($this->getPath()) . "/";
    }
}
