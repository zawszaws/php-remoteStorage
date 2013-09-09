<?php

namespace fkooman\RemoteStorage\Dummy;

use fkooman\RemoteStorage\StorageInterface;
use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Folder;
use fkooman\RemoteStorage\Node;

class DummyStorage implements StorageInterface
{
    public function getFolder(Path $path)
    {
        return new Folder(
            array(
                "foo.txt" => new Node(2),
                "bar.txt" => new Node(3),
                "bar/" => new Node(4)
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
        return new Node(6);
    }

    public function deleteDocument(Path $path)
    {
        return new Node(7);
    }
}
