<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\RemoteStorage;
use fkooman\RemoteStorage\DummyStorage;
use fkooman\RemoteStorage\PathParser;

use fkooman\oauth\rs\TokenIntrospection;

class RemoteStorageTest extends \PHPUnit_Framework_TestCase
{
    private $remoteStorage;

    public function setUp()
    {
        $tokenIntrospection = new TokenIntrospection(
            array(
                "active" => true,
                "sub" => "admin",
                "scope" => "foo:r bar:rw"
            )
        );
        $this->remoteStorage = new RemoteStorage(new DummyStorage(), $tokenIntrospection);
    }

    public function testGetFile()
    {
        $this->remoteStorage->getFile(new PathParser("/admin/foo/bar.txt"));
    }

    public function testPutFile()
    {
        $this->remoteStorage->putFile(new PathParser("/admin/bar/foo.txt"), "Hello World!", "text/plain");
    }

    public function testGetDir()
    {
        $this->remoteStorage->getDir(new PathParser("/admin/foo/"));
    }

    public function testDeleteFile()
    {
        $this->remoteStorage->deleteFile(new PathParser("/admin/bar/bar.txt"));
    }
}
