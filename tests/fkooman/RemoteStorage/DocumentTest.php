<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Document;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleDocument()
    {
        $document = new Document("Document Content", "text/plain");
        $this->assertEquals(1, $document->getRevisionId());
        $this->assertEquals("Document Content", $document->getContent());
        $this->assertEquals("text/plain", $document->getMimeType());
    }

    public function testSimpleExistingDocument()
    {
        $document = new Document("Document Content", "text/plain", 55);
        $this->assertEquals(55, $document->getRevisionId());
        $this->assertEquals("Document Content", $document->getContent());
        $this->assertEquals("text/plain", $document->getMimeType());
    }
}
