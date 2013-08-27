<?php

namespace fkooman\RemoteStorage;

class DummyStorage implements StorageInterface
{
    public function getDir($dirPath)
    {
        return new Directory(
            123456,
            array(
                "foo.txt" => new Entity(654321),
                "bar.txt" => new Entity(112233),
                "bar/" => new Entity(665544)
            )
        );
    }

    public function getFile($filePath)
    {
        return new File(443322, "Hello World!", "text/plain");
    }

    public function putFile($filePath, $fileData, $mimeType)
    {
        return true;
    }

    public function deleteFile($filePath)
    {
        return true;
    }
}
