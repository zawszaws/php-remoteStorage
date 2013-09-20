<?php

namespace fkooman\RemoteStorage\Dummy;

use fkooman\RemoteStorage\StorageInterface;
use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Folder;

use fkooman\RemoteStorage\Exception\FolderException;
use fkooman\RemoteStorage\Exception\DocumentException;

use fkooman\RemoteStorage\File\MetadataInterface;

class DummyStorage implements StorageInterface
{
    /** @var fkooman\RemoteStorage\MetadataInterface */
    private $metadataHandler;

    /** @var array */
    private $documentStore;

    public function __construct(MetadataInterface $metadataHandler)
    {
        $this->metadataHandler = $metadataHandler;
        $this->documentStore = array();
    }

    public function getFolder(Path $folderPath)
    {
        if (!$folderPath->getIsFolder()) {
            throw new FolderException("not a folder");
        }
        $pathParts = $folderPath->getPathParts();
        $currentFolder = $this->documentStore;

        for ($i = 0; $i < count($pathParts) - 1; $i++) {
            if (!isset($currentFolder[$pathParts[$i]]) || !is_array($currentFolder[$pathParts[$i]])) {
                throw new FolderException("folder not found");
            }
            $currentFolder = $currentFolder[$pathParts[$i]];
        }

        $folderList = array();
        foreach ($currentFolder as $key => $value) {
            if (is_array($value)) {
                $key .= "/";
            }
            $entryMetadata = $this->metadataHandler->getMetadata(new Path($folderPath->getPath() . $key));
            $folderList[$key] = $entryMetadata['revisionId'];
        }
        $folderMetadata = $this->metadataHandler->getMetadata($folderPath);

        return new Folder($folderList, $folderMetadata['revisionId']);
    }

    public function getDocument(Path $documentPath)
    {
        if (!$documentPath->getIsDocument()) {
            throw new DocumentException("not a document");
        }
        $pathParts = $documentPath->getPathParts();
        $currentFolder = $this->documentStore;

        for ($i = 0; $i < count($pathParts) - 1; $i++) {
            $newFolder = $currentFolder[$pathParts[$i]];
            if (!isset($newFolder) || !is_array($newFolder)) {
                throw new DocumentException("document not found");
            }
            $currentFolder = $newFolder;
        }
        if (!isset($currentFolder[$pathParts[count($pathParts)-1]])) {
            throw new DocumentException("document not found");
        }
        $documentContent = $currentFolder[$pathParts[count($pathParts)-1]];
        $documentMetadata = $this->metadataHandler->getMetadata($documentPath);

        return new Document($documentContent, $documentMetadata['mimeType'], $documentMetadata['revisionId']);
    }

    public function putDocument(Path $documentPath, Document $document)
    {
        // FIXME: what if trying to put to file? or what if part of the path
        // is a file?!
        $this->storeDocument($this->documentStore, $documentPath->getPathParts(), $document->getContent());
        // store metadata
        $this->metadataHandler->setMetadata(
            $documentPath,
            $document->getMimeType(),
            $document->getRevisionId()
        );

        return true;
    }

    private function storeDocument(array &$documentStore, array $restOfPath, &$documentContent)
    {
        $part = array_shift($restOfPath);
        if (0 < count($restOfPath)) {
            if (!isset($documentStore[$part])) {
                $documentStore[$part] = array();
            }
            $this->storeDocument($documentStore[$part], $restOfPath, $documentContent);
        } else {
            // FIXME: check if restOfPath is folder
            $documentStore[$part] = $documentContent;
        }
    }

    public function deleteDocument(Path $documentPath)
    {
        $this->removeDocument($this->documentStore, $documentPath->getPathParts());
        // delete metadata
        $this->removeEmptyFolders($this->documentStore);

        return true;
    }

    private function removeDocument(array &$documentStore, array $restOfPath)
    {
        $part = array_shift($restOfPath);
        if (0 < count($restOfPath)) {
            if (!isset($documentStore[$part])) {
                throw new DocumentException("document not found");
            }
            $this->removeDocument($documentStore[$part], $restOfPath);
        } else {
            if (is_array($documentStore[$part])) {
                throw new DocumentException("document is folder");
            }
            unset($documentStore[$part]);
        }
    }

    private function removeEmptyFolders(array &$documentStore)
    {
        foreach ($documentStore as $key => $value) {
            if (is_array($value)) {
                // folder
                if (0 === count($value)) {
                    // empty folder, remove it
                    unset($documentStore[$key]);
                } else {
                    // not empty, recurse in it
                    $this->removeEmptyFolders($documentStore[$key]);
                }
            }
        }
    }

    public function getStore()
    {
        return $this->documentStore;
    }

    public function getMetadataStore()
    {
        return $this->metadataHandler;
    }
}
