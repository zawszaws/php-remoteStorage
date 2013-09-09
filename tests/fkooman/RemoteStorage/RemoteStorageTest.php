<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\RemoteStorage;
use fkooman\RemoteStorage\Dummy\DummyStorage;
use fkooman\RemoteStorage\Path;

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
        $folder = $this->remoteStorage->getFolder(new Path("/admin/foo/"));
        $this->assertEquals(
            '{"foo.txt":2,"bar.txt":3,"bar\/":4}',
            $folder->getContent()
        );
    }

    public function testGetDocument()
    {
        $document = $this->remoteStorage->getDocument(new Path("/admin/foo/bar.txt"));
        $this->assertEquals("Hello World!", $document->getContent());
        $this->assertEquals("text/plain", $document->getMimeType());
        $this->assertEquals(5, $document->getRevisionId());
    }

    public function testPutDocument()
    {
        $node = $this->remoteStorage->putDocument(new Path("/admin/bar/foo.txt"), new Document("Hello World!", "text/plain"));
        $this->assertEquals(1, $node->getRevisionId());
    }

    public function testDeleteDocument()
    {
        $node = $this->remoteStorage->deleteDocument(new Path("/admin/bar/bar.txt"));
        $this->assertEquals(6, $node->getRevisionId());
    }
}
