<?php

namespace fkooman\RemoteStorage\File;

use fkooman\RemoteStorage\File\NullMetadata;
use fkooman\RemoteStorage\Path;

class NullMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testFolderMetadata()
    {
        $nullMetadata = new NullMetadata();
        $nullMetadata->setMetadata(new Path("/admin/foo/bar/baz/"), "application/json", 1);
        $nullMetadata->setMetadata(new Path("/admin/foo/bar/baz/"), "application/json", 2);
        $nullMetadata->setMetadata(new Path("/admin/foo/bar/baz/"), "application/json", 3);

        $metadata = $nullMetadata->getMetadata(new Path("/admin/foo/bar/baz/"));
        $this->assertEquals(3, $metadata['revisionId']);
        $this->assertEquals("application/json", $metadata['mimeType']);

        $metadata = $nullMetadata->getMetadata(new Path("/admin/foo/bar/"));
        $this->assertEquals(3, $metadata['revisionId']);

        $metadata = $nullMetadata->getMetadata(new Path("/admin/foo/"));
        $this->assertEquals(3, $metadata['revisionId']);
    }

    public function testDocumentMetadata()
    {
        $nullMetadata = new NullMetadata();
        $nullMetadata->setMetadata(new Path("/admin/foo/bar/baz.txt"), "text/plain");

        $metadata = $nullMetadata->getMetadata(new Path("/admin/foo/bar/baz.txt"));
        $this->assertEquals(1, $metadata['revisionId']);
        $this->assertEquals("text/plain", $metadata['mimeType']);

        $metadata = $nullMetadata->getMetadata(new Path("/admin/foo/bar/"));
        $this->assertEquals(1, $metadata['revisionId']);
        $this->assertEquals("application/json", $metadata['mimeType']);

    }
}
