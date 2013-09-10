<?php

namespace fkooman\RemoteStorage;

interface StorageInterface
{
    /** @return Folder */
    public function getFolder(Path $path);

    /** @return Document */
    public function getDocument(Path $path);

    /** @return Document */
    public function putDocument(Path $document, Document $document);

    /** @return Document */
    public function deleteDocument(Path $document);
}
