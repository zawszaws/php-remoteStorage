<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\File\FileStorage;
use fkooman\RemoteStorage\File\NullMetadata;
use fkooman\RemoteStorage\File\Exception\FileStorageException;

use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Path;

class FileStorageTest extends \PHPUnit_Framework_TestCase
{
    private $fileStorage;
    private $baseDirectory;

    public function setUp()
    {
        $this->baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        $this->fileStorage = new FileStorage(new NullMetadata(), $this->baseDirectory);
        $this->fileStorage->putDocument(new Path("/admin/foo/foo.txt"), new Document("Hello World!", "text/plain"));
        $this->fileStorage->putDocument(
            new Path("/admin/foo/bar/foobar.txt"),
            new Document("Hello World!", "text/plain")
        )   ;
    }

    public function testGetFolder()
    {
        $this->assertEquals(
            '{"bar\/":1,"foo.txt":1}',
            $this->fileStorage->getFolder(new Path("/admin/foo/"))->getContent()
        );
        $this->assertEquals(
            '{"foobar.txt":1}',
            $this->fileStorage->getFolder(new Path("/admin/foo/bar/"))->getContent()
        );
    }

    public function testGetDocument()
    {
        //var_dump($this->fileStorage->getDocument(new Path("/admin/foo/foo.txt"))->getMimeType());
        $this->assertEquals(
            "Hello World!",
            $this->fileStorage->getDocument(new Path("/admin/foo/foo.txt"))->getContent()
        );
        $this->assertEquals(
            "text/plain",
            $this->fileStorage->getDocument(new Path("/admin/foo/foo.txt"))->getMimeType()
        );
    }

    public function testPutDocument()
    {
        $this->assertTrue(
            $this->fileStorage->putDocument(
                new Path("/admin/foo/hello.json"),
                new Document('{"hello": "world"}', "application/json")
            )
        );
    }

    public function testDeleteDocument()
    {
        $this->assertTrue($this->fileStorage->deleteDocument(new Path("/admin/foo/foo.txt")));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage unable to change to folder
     */
    public function testGetFolderOnDocument()
    {
        $this->fileStorage->getFolder(new Path("/admin/foo/foo.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage path points to folder, not document
     */
    public function testGetDocumentOnFolder()
    {
        $this->fileStorage->getDocument(new Path("/admin/foo/"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage unable to read document
     */
    public function testGetDocumentOnNonExistingDocument()
    {
        $this->fileStorage->getDocument(new Path("/admin/foo/not-there.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\File\Exception\FileStorageException
     * @expectedExceptionMessage unable to change to folder
     */
    public function testGetFolderOnNonExistingFolder()
    {
        $this->fileStorage->getFolder(new Path("/folder/not/there/"));
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
