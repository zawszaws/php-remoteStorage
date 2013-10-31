<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\File\MockMetadata;
use fkooman\RemoteStorage\Path;

class MockMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testFolderMetadata()
    {
        $mockMetadata = new MockMetadata();
        $mockMetadata->setMetadata(new Path("/admin/foo/bar/baz/"), "application/json", 1);
        $mockMetadata->setMetadata(new Path("/admin/foo/bar/baz/"), "application/json", 2);
        $mockMetadata->setMetadata(new Path("/admin/foo/bar/baz/"), "application/json", 3);

        $metadata = $mockMetadata->getMetadata(new Path("/admin/foo/bar/baz/"));
        $this->assertEquals(3, $metadata['revisionId']);
        $this->assertEquals("application/json", $metadata['mimeType']);

        $metadata = $mockMetadata->getMetadata(new Path("/admin/foo/bar/"));
        $this->assertEquals(3, $metadata['revisionId']);

        $metadata = $mockMetadata->getMetadata(new Path("/admin/foo/"));
        $this->assertEquals(3, $metadata['revisionId']);
    }

    public function testDocumentMetadata()
    {
        $mockMetadata = new MockMetadata();
        $mockMetadata->setMetadata(new Path("/admin/foo/bar/baz.txt"), "text/plain");

        $metadata = $mockMetadata->getMetadata(new Path("/admin/foo/bar/baz.txt"));
        $this->assertEquals(1, $metadata['revisionId']);
        $this->assertEquals("text/plain", $metadata['mimeType']);

        $metadata = $mockMetadata->getMetadata(new Path("/admin/foo/bar/"));
        $this->assertEquals(1, $metadata['revisionId']);
        $this->assertEquals("application/json", $metadata['mimeType']);
    }
}
