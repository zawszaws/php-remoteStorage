<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Dummy\DummyStorage;
use fkooman\OAuth\ResourceServer\ResourceServer;
use fkooman\Http\Request;

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
        );
        $client = new \Guzzle\Http\Client("https://auth.example.org/introspect");
        $client->addSubscriber($plugin);

        $resourceServer = new ResourceServer($client);

        $this->requestHandler = new RequestHandler(
            new DummyStorage(),
            $resourceServer
        );
    }

    public function testGetDocument()
    {
        $request = new Request("http://example.org/admin/foo/bar.txt", "GET");
        $request->setPathInfo("/admin/foo/bar.txt");
        $request->setHeader("Authorization", "Bearer foo");

        $response = $this->requestHandler->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("text/plain", $response->getHeader("Content-Type"));
        $this->assertEquals("Hello World!", $response->getContent());
        $this->assertEquals(5, $response->getHeader("ETag"));
    }

    public function testGetFolder()
    {
        $request = new Request("http://example.org/admin/foo/", "GET");
        $request->setPathInfo("/admin/foo/");
        $request->setHeader("Authorization", "Bearer foo");

        $response = $this->requestHandler->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeader("Content-Type"));
        $this->assertEquals(1, $response->getHeader("ETag"));
        $this->assertEquals('{"foo.txt":2,"bar.txt":3,"bar\/":4}', $response->getContent());
    }
    /*
    public function testPutDocument()
    {
        $request = Request::create(
            "http://example.org/admin/foo/bar.txt",
            "PUT",
            array(),
            array(),
            array(),
            array(),
            "Hello World!"
        );
        $request->headers->set("Authorization", "Bearer foo");
        $request->headers->set("Content-Type", "text/plain");

        $response = $this->requestHandler->put($request, $this->diContainer, "admin/foo/bar.txt");
        // FIXME: statuscode should be 201
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(5, $response->headers->get("ETag"));
    }

    public function testDeleteDocument()
    {
        $request = Request::create("http://example.org/admin/foo/bar.txt", "DELETE");
        $request->headers->set("Authorization", "Bearer foo");

        $response = $this->requestHandler->delete($request, $this->diContainer, "admin/foo/bar.txt");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(5, $response->headers->get("ETag"));
    }
    */
}
