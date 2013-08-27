<?php

namespace fkooman\RemoteStorage;

class FileStorage implements StorageInterface
{
    /** @var MimeHandlerInterface */
    private $mimeHandler;

    /** @var string */
    private $baseDirectory;

    public function __construct(MimeHandlerInterface $mimeHandler, $baseDirectory)
    {
        $this->mimeHandler = $mimeHandler;
        $this->baseDirectory = $baseDirectory;
    }

    public function getDir($dirPath)
    {
        $dirPath = $this->baseDirectory . $dirPath;
        $currentDirectory = getcwd();
        if (false === @chdir($dirPath)) {
            return false;
        }
        $dirList = array();
        foreach (glob("*", GLOB_MARK) as $entry) {
            $dirList[$entry] = new Entity($this->getEntityTag($dirPath . "/" . $entry));
        }
        // the chdir below MUST always work...
        @chdir($currentDirectory);

        return new Directory($this->getEntityTag($dirPath), $dirList);

        return $dirList;
    }

    public function getFile($filePath)
    {
        $filePath = $this->baseDirectory . $filePath;

        if (is_dir($filePath)) {
            return false;
        }
        $fileContent = @file_get_contents($filePath);
        if (false === $fileContent) {
            return false;
        }

        $mimeType = $this->mimeHandler->getMimeType($filePath);
        $entityTag = $this->getEntityTag($filePath);

        return new File($entityTag, $fileContent, $mimeType);
    }

    public function putFile($filePath, $fileData, $mimeType)
    {
        $filePath = $this->baseDirectory . $filePath;

        if (false === @file_put_contents($filePath, $fileData)) {
            if (false === $this->createDirectory(dirname($filePath))) {
                return false;
            }
            if (false === @file_put_contents($filePath, $fileData)) {
                return false;
            }
        }

        $this->mimeHandler->setMimeType($filePath, $mimeType);

        return true;
    }

    public function deleteFile($filePath)
    {
        // FIXME: if directory is now empty, the dir should also be removed

        $filePath = $this->baseDirectory . $filePath;

        return @unlink($filePath);
    }

    private function createDirectory($dirPath)
    {
        return @mkdir($dirPath, 0775, true);
    }

    private function getEntityTag($entityPath)
    {
        return filemtime($entityPath);
    }

    private function validatePath($entityPath)
    {
        $realPath = realpath($this->baseDirectory . $filePath);
        if (false === $realPath || !is_string($realPath) || 0 >= strlen($realPath)) {
            throw new FileStorageException("invalid path");
        }
        if (0 !== strpos($realPath, $this->baseDirectory)) {
            throw new FileStorageException("path outside base directory");
        }

        return $realPath;
    }

}
