<?php

namespace fkooman\RemoteStorage\Dummy;

use fkooman\RemoteStorage\StorageInterface;
use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Folder;

class DummyStorage implements StorageInterface
{
    public function getFolder(Path $path)
    {
        return new Folder(
            array(
                "foo.txt" => 2,
                "bar.txt" => 3,
                "bar/" => 4
            ),
            1
        );
    }

    public function getDocument(Path $path)
    {
        return new Document("Hello World!", "text/plain", 5);
    }

    public function putDocument(Path $path, Document $document)
    {
        return new Document($document->getContent(), $document->getMimeType(), $document->getRevisionId());
    }

    public function deleteDocument(Path $path)
    {
        return new Document("Hello World!", "text/plain", 6);
    }
}
