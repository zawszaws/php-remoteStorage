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
        $pathParts = $folderPath->getPathParts();
        $currentFolder = $this->documentStore;

        for ($i = 0; $i < count($pathParts) - 1; $i++) {
            $newFolder = $currentFolder[$pathParts[$i]];
            if (!isset($newFolder) || !is_array($newFolder)) {
                throw new FolderException("folder not found");
            }
            $currentFolder = $newFolder;
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

    public function deleteDocument(Path $documentPath)
    {
        // FIXME: also delete all folders above this entry that are empty
        $pathParts = $documentPath->getPathParts();
        $currentFolder = $this->documentStore;

        for ($i = 0; $i < count($pathParts) - 1; $i++) {
            $newFolder = $currentFolder[$pathParts[$i]];
            if (!isset($newFolder) || !is_array($newFolder)) {
                throw new DocumentException("document not found");
            }
            $currentFolder = $newFolder;
        }
        if (!isset($currentFolder[$pathParts[0]])) {
            throw new DocumentException("document not found");
        }
        unset($currentFolder[$pathParts[0]]);

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

    public function getStore()
    {
        return $this->documentStore;
    }

    public function getMetadataStore()
    {
        return $this->metadataHandler;
    }
}
