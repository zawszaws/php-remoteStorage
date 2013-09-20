<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Dummy\DummyStorage;
use fkooman\RemoteStorage\File\NullMetadata;
use fkooman\RemoteStorage\File\Exception\FileStorageException;

use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Path;

class DummyStorageTest extends \PHPUnit_Framework_TestCase
{
    private $documentStorage;

    public function setUp()
    {
        $this->documentStorage = new DummyStorage(new NullMetadata());
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
            $this->documentStorage->getFolder(
                new Path("/admin/foo/")
            )->getContent()
        );
        $this->assertEquals(
            '{"foobar.txt":1}',
            $this->documentStorage->getFolder(new Path("/admin/foo/bar/"))->getContent()
        );
    }

    public function testGetDocument()
    {
        //var_dump($this->documentStorage->getDocument(new Path("/admin/foo/foo.txt"))->getMimeType());
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
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage unable to change to folder
     */
    public function testGetFolderOnDocument()
    {
        $this->documentStorage->getFolder(new Path("/admin/foo/foo.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage path points to folder, not document
     */
    public function testGetDocumentOnFolder()
    {
        $this->documentStorage->getDocument(new Path("/admin/foo/"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage unable to read document
     */
    public function testGetDocumentOnNonExistingDocument()
    {
        $this->documentStorage->getDocument(new Path("/admin/foo/not-there.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage unable to change to folder
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
