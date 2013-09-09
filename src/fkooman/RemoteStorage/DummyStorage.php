<?php

namespace fkooman\RemoteStorage;

class DummyStorage implements StorageInterface
{
    public function getFolder(PathParser $folderPath)
    {
        return new Folder(
            123456,
            array(
                "foo.txt" => new Node(654321),
                "bar.txt" => new Node(112233),
                "bar/" => new Node(665544)
            )
        );
    }

    public function getDocument(PathParser $documentPath)
    {
        return new Document(443322, "Hello World!", "text/plain");
    }

    public function putDocument(PathParser $documentPath, $documentData, $documentMimeType)
    {
        return new Node("918273");
    }

    public function deleteDocument(PathParser $documentPath)
    {
        return new Node("11111111");
    }
}
