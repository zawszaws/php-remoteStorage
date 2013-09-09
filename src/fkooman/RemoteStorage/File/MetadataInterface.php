<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Node;

interface MetadataInterface
{
    public function setMetadata(Path $path, Node $node);
    public function getMetadata(Path $path);
}
