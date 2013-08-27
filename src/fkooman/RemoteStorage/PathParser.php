<?php

namespace fkooman\RemoteStorage;

class PathParser
{
    private $userId;
    private $isPublic;
    private $moduleName;
    private $isDirectory;
    private $entityPath;

    public function __construct($entityPath)
    {
//        $pathRegularExpression = '^/[a-zA-Z0-9%.-_]+/(?:(?:public/)|(?!public)[a-zA-Z0-9%.-_]+/)[a-zA-Z0-9%.-_]+/(?:[a-zA-Z0-9%.-_]+/)*(?:[a-zA-Z0-9%.-_]+)*$';

        if (0 !== strpos($entityPath, "/")) {
            throw new PathParserException("path MUST start with a '/'");
        }

        $entityParts = explode("/", $entityPath);
        $partCount = count($entityParts);
        if (4 > $partCount) {
            throw new PathParserException("path MUST include user and category directory");
        }
        if ("public" === $entityParts[2]) {
            // if public, the entityParts need to contain an extra as "public" does not count then
            if (5 > $partCount) {
                throw new PathParserException("public path MUST include user and category directory");
            }
        }

        // all parts should only consist of allowed characters, the last one is
        // allowed to be empty as well
        for ($i = 1; $i < $partCount-1; $i++) {
            if (0 === preg_match('/^[a-zA-Z0-9%.-_]+$/', $entityParts[$i])) {
                throw new PathParserException("path part contains invalid characters");
            }
        }
        if (!empty($entityParts[$partCount-1])) {
            if (0 === preg_match('/^[a-zA-Z0-9%.-_]+$/', $entityParts[$partCount-1])) {
                throw new PathParserException("path part contains invalid characters");
            }
        }

        $this->userId = $entityParts[1];
        $this->isPublic = "public" === $entityParts[2];
        $this->moduleName = ($this->isPublic) ? $entityParts[3] : $entityParts[2];
        $this->isDirectory = empty($entityParts[count($entityParts)-1]);
        $this->entityPath = $entityPath;
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

    public function getIsDirectory()
    {
        return $this->isDirectory;
    }

    public function getIsFile()
    {
        return !$this->isDirectory;
    }

    public function getEntityPath()
    {
        return $this->entityPath;
    }
}
