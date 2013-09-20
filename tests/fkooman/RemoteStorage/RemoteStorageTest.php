<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Dummy\DummyStorage;
use fkooman\OAuth\ResourceServer\ResourceServer;
use fkooman\RemoteStorage\File\NullMetadata;

class RemoteStorageTest extends \PHPUnit_Framework_TestCase
{
    private $remoteStorage;

    public function setUp()
    {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(
            new \Guzzle\Http\Message\Response(
                200,
                null,
                '{"active": true, "sub": "admin", "scope": "foo:rw bar:rw"}'
            )
        );
        $client = new \Guzzle\Http\Client("https://auth.example.org/introspect");
        $client->addSubscriber($plugin);

        $resourceServer = new ResourceServer($client);
        $resourceServer->setAuthorizationHeader("Bearer foo");

        $this->remoteStorage = new RemoteStorage(new DummyStorage(new NullMetadata()), $resourceServer);
        $this->remoteStorage->putFile(new Path("/admin/foo/bar.txt"), new Document("Hello World!", "text/plain", 5));
    }

    public function testGetFolder()
    {
        $folder = $this->remoteStorage->getFolder(new Path("/admin/foo/"), null);
        $this->assertEquals(
            '{"foo.txt":2,"bar.txt":3,"bar\/":4}',
            $folder->getContent()
        );
    }

    public function testGetFolderCurrentVersion()
    {
        $this->assertNull($this->remoteStorage->getFolder(new Path("/admin/foo/"), 1));
    }

    public function testGetDocument()
    {
        $document = $this->remoteStorage->getDocument(new Path("/admin/foo/bar.txt"), null);
        $this->assertEquals("Hello World!", $document->getContent());
        $this->assertEquals("text/plain", $document->getMimeType());
        $this->assertEquals(5, $document->getRevisionId());
    }

    public function testPutDocument()
    {
        $node = $this->remoteStorage->putDocument(
            new Path("/admin/bar/foo.txt"),
            new Document("Hello World!", "text/plain"),
            null,
            null
        );
        $this->assertEquals(6, $node->getRevisionId());
    }

    public function testDeleteDocument()
    {
        $node = $this->remoteStorage->deleteDocument(new Path("/admin/bar/bar.txt"), null);
        $this->assertEquals(6, $node->getRevisionId());
    }
}
