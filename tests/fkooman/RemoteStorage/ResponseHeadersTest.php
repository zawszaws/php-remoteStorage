<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\ResponseHeaders;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Folder;

class ResponseHeadersTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleResponse()
    {
        $responseHeaders = new ResponseHeaders();
        $this->assertEquals(
            array(
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Authorization, If-None-Match, Content-Type, Origin, ETag'
            ),
            $responseHeaders->getHeaders(null, "*")
        );
    }

    public function testDocumentResponse()
    {
        $responseHeaders = new ResponseHeaders();
        $this->assertEquals(
            array(
                'Access-Control-Allow-Origin' => 'www.example.org',
                'Access-Control-Allow-Methods' => 'GET, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Authorization, If-None-Match, Content-Type, Origin, ETag',
                'ETag' => '11',
                'Content-Type' => 'text/plain'
            ),
            $responseHeaders->getHeaders(
                new Document("Hello World", "text/plain", 11),
                "www.example.org"
            )
        );
    }

    public function testFolderResponse()
    {
        $responseHeaders = new ResponseHeaders();
        $this->assertEquals(
            array(
                'Access-Control-Allow-Origin' => 'www.example.org',
                'Access-Control-Allow-Methods' => 'GET, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Authorization, If-None-Match, Content-Type, Origin, ETag',
                'ETag' => '4',
                'Content-Type' => 'application/json'
            ),
            $responseHeaders->getHeaders(new Folder(array("foo" => 123), 4), "www.example.org")
        );
    }
}
