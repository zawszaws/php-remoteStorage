<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\File\FileStorage;
use fkooman\OAuth\ResourceServer\ResourceServer;
use fkooman\Http\Request;
use fkooman\RemoteStorage\File\MockMetadata;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Path;

class RequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $requestHandler;
    private $diContainer;

    public function setUp()
    {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(
            new \Guzzle\Http\Message\Response(
                200,
                null,
                '{"active": true, "sub": "admin", "scope": "foo:rw"}'
            )
        )->addResponse(
            new \Guzzle\Http\Message\Response(
                200,
                null,
                '{"active": true, "sub": "admin", "scope": "foo:rw"}'
            )
        )->addResponse(
            new \Guzzle\Http\Message\Response(
                200,
                null,
                '{"active": true, "sub": "admin", "scope": "foo:rw"}'
            )
        );
        $client = new \Guzzle\Http\Client("https://auth.example.org/introspect");
        $client->addSubscriber($plugin);

        $resourceServer = new ResourceServer($client);

        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        $documentStore = new FileStorage(new MockMetadata(), $baseDirectory);

        $documentStore->putDocument(
            new Path("/admin/foo/foo.txt"),
            new Document("Hello World!", "text/plain")
        );
        $documentStore->putDocument(
            new Path("/admin/foo/bar/foobar.txt"),
            new Document("Hello World!", "text/plain")
        );

        $this->requestHandler = new RequestHandler($documentStore, $resourceServer);
    }

    public function testGetDocument()
    {
        $request = new Request("http://example.org/admin/foo/bar.txt", "GET");
        $request->setPathInfo("/admin/foo/foo.txt");
        $request->setHeader("Authorization", "Bearer foo");

        $response = $this->requestHandler->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("text/plain", $response->getHeader("Content-Type"));
        $this->assertEquals("Hello World!", $response->getContent());
        $this->assertEquals(1, $response->getHeader("ETag"));
    }

    public function testGetFolder()
    {
        $request = new Request("http://example.org/admin/foo/", "GET");
        $request->setPathInfo("/admin/foo/");
        $request->setHeader("Authorization", "Bearer foo");

        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeader("Content-Type"));
        $this->assertEquals(2, $response->getHeader("ETag"));
        $this->assertEquals('{"foo.txt":1,"bar\/":1}', $response->getContent());
    }

    public function testPutDocument()
    {
        $request = new Request("http://example.org/admin/foo/bar.txt", "PUT");
        $request->setPathInfo('/admin/foo/bar.txt');
        $request->setContent("Hello World!");
        $request->setContentType("text/plain");
        $request->setHeader("Authorization", "Bearer foo");

        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode()); // FIXME: statuscode should be 201
        $this->assertEquals(1, $response->getHeader("ETag"));
        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $response->getHeader("ETag"));
        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $response->getHeader("ETag"));
    }

    public function testDeleteDocument()
    {
        $request = new Request("http://example.org/admin/foo/bar.txt", "DELETE");
        $request->setPathInfo("/admin/foo/foo.txt");
        $request->setHeader("Authorization", "Bearer foo");
        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $response->getHeader("ETag"));
    }

    public function testOptionsDocument()
    {
        $request = new Request("http://example.org/admin/foo/bar.txt", "OPTIONS");
        $request->setPathInfo("/admin/foo/foo.txt");
        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsFolder()
    {
        $request = new Request("http://example.org/admin/foo/", "OPTIONS");
        $request->setPathInfo("/admin/foo/");
        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
