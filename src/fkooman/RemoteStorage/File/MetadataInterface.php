<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\Path;

interface MetadataInterface
{
    public function setMetadata(Path $path, $mimeType, $revisionId);
    public function getMetadata(Path $path);
}
