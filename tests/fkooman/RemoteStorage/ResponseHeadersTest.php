<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\ResponseHeaders;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Node;
use fkooman\RemoteStorage\Folder;

class ResponseHeadersTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleResponse()
    {
        $ResponseHeaders = new ResponseHeaders();
        $this->assertEquals(
            array(
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Authorization, If-None-Match, Content-Type, Origin, ETag'
            ),
            $ResponseHeaders->getHeaders(null, "*")
        );
    }

    public function testDocumentResponse()
    {
        $ResponseHeaders = new ResponseHeaders();
        $this->assertEquals(
            array(
                'Access-Control-Allow-Origin' => 'www.example.org',
                'Access-Control-Allow-Methods' => 'GET, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Authorization, If-None-Match, Content-Type, Origin, ETag',
                'ETag' => '12345',
                'Content-Type' => 'text/plain'
            ),
            $ResponseHeaders->getHeaders(
                new Document(12345, "Hello World", "text/plain"),
                "www.example.org"
            )
        );
    }

    public function testFolderResponse()
    {
        $ResponseHeaders = new ResponseHeaders();
        $this->assertEquals(
            array(
                'Access-Control-Allow-Origin' => 'www.example.org',
                'Access-Control-Allow-Methods' => 'GET, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Authorization, If-None-Match, Content-Type, Origin, ETag',
                'ETag' => '54321',
                'Content-Type' => 'application/json'
            ),
            $ResponseHeaders->getHeaders(new Folder(54321, array(new Node("54321"))), "www.example.org")
        );
    }
}
