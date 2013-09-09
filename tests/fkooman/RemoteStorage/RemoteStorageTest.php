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

    public function testGetFolder()
    {
        $folder = $this->remoteStorage->getFolder(new PathParser("/admin/foo/"));
        $this->assertEquals(
            array(
                'foo.txt' => '654321',
                'bar.txt' => '112233',
                'bar/' => '665544'
            ),
            $folder->getFlatFolderList()
        );
    }

    public function testGetDocument()
    {
        $document = $this->remoteStorage->getDocument(new PathParser("/admin/foo/bar.txt"));
        $this->assertEquals("Hello World!", $document->getContent());
        $this->assertEquals("text/plain", $document->getMimeType());
        $this->assertEquals("443322", $document->getEntityTag());
    }

    public function testPutDocument()
    {
        $node = $this->remoteStorage->putDocument(new PathParser("/admin/bar/foo.txt"), "Hello World!", "text/plain");
        $this->assertEquals("918273", $node->getEntityTag());
    }

    public function testDeleteDocument()
    {
        $node = $this->remoteStorage->deleteDocument(new PathParser("/admin/bar/bar.txt"));
        $this->assertEquals("11111111", $node->getEntityTag());
    }
}
