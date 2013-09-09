<?php

namespace fkooman\RemoteStorage;

interface StorageInterface
{
    /** @return Folder */
    public function getFolder(Path $path);

    /** @return Document */
    public function getDocument(Path $path);

    /** @return Node */
    public function putDocument(Path $document, Document $document);

    /** @return Node */
    public function deleteDocument(Path $document);
}
