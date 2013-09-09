<?php

namespace fkooman\RemoteStorage;

interface StorageInterface
{
    public function getFolder(PathParser $folderPath);
    public function getDocument(PathParser $documentPath);
    public function putDocument(PathParser $documentPath, $documentData, $documentMimeType);
    public function deleteDocument(PathParser $documentPath);
}
