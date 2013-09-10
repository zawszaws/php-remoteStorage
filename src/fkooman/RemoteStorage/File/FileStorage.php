<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\StorageInterface;
use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Folder;
use fkooman\RemoteStorage\File\Exception\FileStorageException;

class FileStorage implements StorageInterface
{
    /** @var MetadataInterface */
    private $metadataHandler;

    /** @var string */
    private $storageRoot;

    public function __construct(MetadataInterface $metadataHandler, $storageRoot)
    {
        $this->metadataHandler = $metadataHandler;
        $this->storageRoot = $storageRoot;
    }

    public function getFolder(Path $path)
    {
        $folderPath = $this->storageRoot . $path->getPath();

        $currentFolder = getcwd();
        if (false === @chdir($folderPath)) {
            throw new FileStorageException("unable to change to folder");
        }
        $folderList = array();
        foreach (glob("*", GLOB_MARK) as $entry) {
            $metadata = $this->metadataHandler->getMetadata(new Path($folderPath . $entry));
            $folderList[$entry] = $metadata['revisionId'];
        }
        // the chdir below MUST always work...
        @chdir($curentFolder);

        $metadata = $this->metadataHandler->getMetadata(new Path($folderPath));

        return new Folder($folderList, $metadata['revisionId']);
    }

    public function getDocument(Path $path)
    {
        $documentPath = $this->storageRoot . $path->getPath();

        if (is_dir($documentPath)) {
            throw new FileStorageException("path points to folder, not document");
        }
        $documentContent = @file_get_contents($documentPath);
        if (false === $documentContent) {
            throw new FileStorageException("unable to read document");
        }

        $documentMetadata = $this->metadataHandler->getMetadata(new Path($documentPath));

        return new Document($documentContent, $documentMetadata['mimeType'], $documentMetadata['revisionId']);
    }

    public function putDocument(Path $path, Document $document)
    {
        $documentPath = $this->storageRoot . $path->getPath();

        if (false === @file_put_contents($documentPath, $document->getContent())) {
            if (false === $this->createFolder(dirname($documentPath))) {
                throw new FileStorageException("unable to create folder");
            }
            if (false === @file_put_contents($documentPath, $document->getContent())) {
                throw new FileStorageException("unable to store document");
            }
        }

        // FIXME: if put failed because folder name --> 400
        // FIXME: if put succeeded with new file --> 201?
        // FIXME: how to figure out if this is a new file?
        $this->metadataHandler->setMetadata(
            new Path($documentPath),
            $document->getMimeType(),
            $document->getRevisionId()
        );
        // update all revisions from directories above

        // FIXME: return Node with ETag
        return true;
    }

    public function deleteDocument(Path $path)
    {
        // FIXME: if folder is now empty, the folder should also be removed,
        // and all empty parent folders as well...

        $documentPath = $this->storageRoot . $path->getPath();

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
