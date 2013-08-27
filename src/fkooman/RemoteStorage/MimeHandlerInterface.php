<?php

namespace fkooman\RemoteStorage;

interface MimeHandlerInterface
{
    public function setMimeType($filePath, $mimeType);
    public function getMimeType($filePath);
}
