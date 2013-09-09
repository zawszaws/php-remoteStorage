<?php

namespace fkooman\RemoteStorage;

class FileStorage implements StorageInterface
{
    /** @var MimeHandlerInterface */
    private $mimeHandler;

    /** @var string */
    private $storageRoot;

    public function __construct(MimeHandlerInterface $mimeHandler, $storageRoot)
    {
        $this->mimeHandler = $mimeHandler;
        $this->storageRoot = $storageRoot;
    }

    public function getFolder(PathParser $folderPath)
    {
        $folderPath = $this->storageRoot . $folderPath->getEntityPath();
        $currentFolder = getcwd();
        if (false === @chdir($folderPath)) {
            throw new FileStorageException("unable to change to folder");
        }
        $folderList = array();
        foreach (glob("*", GLOB_MARK) as $entry) {
            $folderList[$entry] = new Node($this->getEntityTag($folderPath . "/" . $entry));
        }
        // the chdir below MUST always work...
        @chdir($curentFolder);

        return new Folder($this->getEntityTag($folderPath), $folderList);
    }

    public function getDocument(PathParser $documentPath)
    {
        $documentPath = $this->storageRoot . $documentPath->getEntityPath();

        if (is_dir($documentPath)) {
            throw new FileStorageException("path points to folder, not document");
        }
        $documentContent = @file_get_contents($documentPath);
        if (false === $documentContent) {
            throw new FileStorageException("unable to read document");
        }

        $documentMimeType = $this->mimeHandler->getMimeType($documentPath);
        $documentEntityTag = $this->getEntityTag($documentPath);

        return new Document($documentEntityTag, $documentContent, $documentMimeType);
    }

    public function putDocument(PathParser $documentPath, $documentData, $documentMimeType)
    {
        $documentPath = $this->storageRoot . $documentPath->getEntityPath();

        if (false === @file_put_contents($documentPath, $documentData)) {
            if (false === $this->createFolder(dirname($documentPath))) {
                throw new FileStorageException("unable to create folder");
            }
            if (false === @file_put_contents($documentPath, $documentData)) {
                throw new FileStorageException("unable to store document");
            }
        }

        // FIXME: if put failed because folder name --> 400
        // FIXME: if put succeeded with new file --> 201?
        $this->mimeHandler->setMimeType($documentPath, $documentMimeType);

        // FIXME: return Node with ETag
        return true;
    }

    public function deleteDocument(PathParser $documentPath)
    {
        // FIXME: if folder is now empty, the folder should also be removed,
        // and all empty parent folders as well...

        $documentPath = $this->storageRoot . $documentPath->getEntityPath();

        // FIXME:
        // if delete failed because not exists --> 404
        // if delete failed because folder --> 400

        // FIXME: return Node with last ETag
        return @unlink($documentPath);
    }

    private function createFolder($folderPath)
    {
        return @mkdir($folderPath, 0775, true);
    }

    private function getEntityTag($entityPath)
    {
        return filemtime($entityPath);
    }

    /*
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
    */
}
