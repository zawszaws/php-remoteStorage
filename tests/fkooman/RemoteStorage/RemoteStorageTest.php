<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Dummy\DummyStorage;
use fkooman\OAuth\ResourceServer\ResourceServer;
use fkooman\RemoteStorage\File\NullMetadata;

use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

class RemoteStorageTest extends \PHPUnit_Framework_TestCase
{
    private $remoteStorage;

    public function setUp()
    {
        $plugin = new MockPlugin();
        // FIXME: why is the response requested twice sometimes?
        // one should be enough!
        $plugin->addResponse(
            new Response(
                200,
                null,
                '{"active": true,"scope": "foo:rw bar:rw","sub": "admin"}'
            )
        )->addResponse(
            new Response(
                200,
                null,
                '{"active": true,"scope": "foo:rw bar:rw","sub": "admin"}'
            )
        );
        $client = new Client("http://foo.example.org/");
        $client->addSubscriber($plugin);

        $resourceServer = new ResourceServer($client);
        $resourceServer->setAuthorizationHeader("Bearer foo");

        $this->remoteStorage = new RemoteStorage(new DummyStorage(new NullMetadata()), $resourceServer);
        $this->remoteStorage->putDocument(
            new Path("/admin/foo/bar.txt"),
            new Document("Hello World!", "text/plain", 5),
            null,
            null
        );
    }

    public function testGetFolder()
    {
        $folder = $this->remoteStorage->getFolder(new Path("/admin/foo/"), null);
        $this->assertEquals(
            '{"bar.txt":5}',
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
        $document = $this->remoteStorage->putDocument(
            new Path("/admin/bar/foo.txt"),
            new Document("Hello World!", "text/plain"),
            null,
            null
        );
        $this->assertEquals(1, $document->getRevisionId());
    }

    public function testDeleteDocument()
    {
        $node = $this->remoteStorage->deleteDocument(new Path("/admin/foo/bar.txt"), null);
        $this->assertEquals(5, $node->getRevisionId());
    }
}
