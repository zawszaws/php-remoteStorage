<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\File\FileStorage;
use fkooman\RemoteStorage\File\MockMetadata;

use fkooman\RemoteStorage\Exception\FolderException;
use fkooman\RemoteStorage\Exception\DocumentException;

class FileStorageTest2 extends \PHPUnit_Framework_TestCase
{
    private $baseDirectory;
    private $documentStorage;

    public function setUp()
    {
        $this->baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        $this->documentStorage = new FileStorage(new MockMetadata(), $this->baseDirectory);

        $this->documentStorage->putDocument(
            new Path("/admin/foo/foo.txt"),
            new Document("Hello World!", "text/plain")
        );
        $this->documentStorage->putDocument(
            new Path("/admin/foo/bar/foobar.txt"),
            new Document("Hello World!", "text/plain")
        );
    }

    public function testGetFolder()
    {
        $this->assertEquals(
            '{"bar\/":1,"foo.txt":1}',
            $this->documentStorage->getFolder(new Path("/admin/foo/"))->getContent()
        );
        $this->assertEquals(
            '{"foobar.txt":1}',
            $this->documentStorage->getFolder(new Path("/admin/foo/bar/"))->getContent()
        );
    }

    public function testGetDocument()
    {
        $this->assertEquals(
            "Hello World!",
            $this->documentStorage->getDocument(new Path("/admin/foo/foo.txt"))->getContent()
        );
        $this->assertEquals(
            "text/plain",
            $this->documentStorage->getDocument(new Path("/admin/foo/foo.txt"))->getMimeType()
        );
    }

    public function testPutDocument()
    {
        $this->assertTrue(
            $this->documentStorage->putDocument(
                new Path("/admin/foo/hello.json"),
                new Document('{"hello": "world"}', "application/json")
            )
        );
    }

    public function testDeleteDocument()
    {
        $this->assertTrue($this->documentStorage->deleteDocument(new Path("/admin/foo/foo.txt")));
    }

    /**
     * @expectedException fkooman\RemoteStorage\Exception\FolderException
     * @expectedExceptionMessage not a folder
     */
    public function testGetFolderOnDocument()
    {
        $this->documentStorage->getFolder(new Path("/admin/foo/foo.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\Exception\DocumentException
     * @expectedExceptionMessage not a document
     */
    public function testGetDocumentOnFolder()
    {
        $this->documentStorage->getDocument(new Path("/admin/foo/"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\Exception\DocumentException
     * @expectedExceptionMessage document not found
     */
    public function testGetDocumentOnNonExistingDocument()
    {
        $this->documentStorage->getDocument(new Path("/admin/foo/not-there.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\Exception\FolderException
     * @expectedExceptionMessage folder not found
     */
    public function testGetFolderOnNonExistingFolder()
    {
        $this->documentStorage->getFolder(new Path("/folder/not/there/"));
    }

    public function tearDown()
    {
        $this->recursiveDelete($this->baseDirectory);
    }

    private function recursiveDelete($folderPath)
    {
        foreach (glob($folderPath . '/*') as $document) {
            if (is_dir($document)) {
                $this->recursiveDelete($document);
            } else {
                unlink($document);
            }
        }
        @rmdir($folderPath);
    }
}
