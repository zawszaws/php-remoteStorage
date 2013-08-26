<?php

namespace fkooman\remotestorage;

interface MimeHandlerInterface
{
    public function setMimeType($filePath, $mimeType);
    public function getMimeType($filePath);
}
