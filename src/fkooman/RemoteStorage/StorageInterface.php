<?php

namespace fkooman\RemoteStorage;

interface StorageInterface
{
    public function getDir($dirPath);
    public function getFile($filePath);
    public function putFile($filePath, $fileData, $mimeType);
    public function deleteFile($filePath);
}
