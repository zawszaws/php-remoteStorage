<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\File\FileStorage;
use fkooman\RemoteStorage\File\NullMetadata;
use fkooman\RemoteStorage\Document;
use fkooman\RemoteStorage\Node;
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
        touch($this->baseDirectory . "/admin/foo/foo.txt", 12345);

        $this->fileStorage->putDocument(new Path("/admin/foo/bar/foobar.txt"), "Hello World!", "text/plain");
        touch($this->baseDirectory . "/admin/foo/bar/foobar.txt", 54321);
        touch($this->baseDirectory . "/admin/foo/bar", 11111);
    }

    public function testGetFolder()
    {
        $this->assertEquals(
            array(
                "bar/" => new Node(11111),
                "foo.txt" => new Node(12345)
            ),
            $this->fileStorage->getFolder(new Path("/admin/foo/"))->getFolderList()
        );
        $this->assertEquals(
            array(
                "foobar.txt" => new Node(54321)
            ),
            $this->fileStorage->getFolder(new Path("/admin/foo/bar/"))->getFolderList()
        );

    }

    public function testGetDocument()
    {
        $this->assertEquals("Hello World!", $this->fileStorage->getDocument(new Path("/admin/foo/foo.txt"))->getContent());
        $this->assertEquals("text/plain", $this->fileStorage->getDocument(new Path("/admin/foo/foo.txt"))->getMimeType());
    }

    public function testPutDocument()
    {
        $this->assertTrue(
            $this->fileStorage->putDocument(
                new Path("/admin/foo/hello.json"),
                '{"hello": "world"}',
                "application/json"
            )
        );
    }

    public function testDeleteDocument()
    {
        $this->assertTrue($this->fileStorage->deleteDocument(new Path("/admin/foo/foo.txt")));
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage unable to change to folder
     */
    public function testGetFolderOnDocument()
    {
        $this->fileStorage->getFolder(new Path("/admin/foo/foo.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage path points to folder, not document
     */
    public function testGetDocumentOnFolder()
    {
        $this->fileStorage->getDocument(new Path("/admin/foo/"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage unable to read document
     */
    public function testGetDocumentOnNonExistingDocument()
    {
        $this->fileStorage->getDocument(new Path("/admin/foo/not-there.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
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
