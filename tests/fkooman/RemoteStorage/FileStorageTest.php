<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\FileStorage;
use fkooman\RemoteStorage\NullMimeHandler;
use fkooman\RemoteStorage\Entity;

class FileStorageTest extends \PHPUnit_Framework_TestCase
{
    private $fileStorage;
    private $baseDirectory;

    public function setUp()
    {
        $this->baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        $this->fileStorage = new FileStorage(new NullMimeHandler(), $this->baseDirectory);

        $this->fileStorage->putFile("/foo.txt", "Hello World!", "text/plain");
        touch($this->baseDirectory . "/foo.txt", 12345);

        $this->fileStorage->putFile("/bar/foobar.txt", "Hello World!", "text/plain");
        touch($this->baseDirectory . "/bar/foobar.txt", 54321);
        touch($this->baseDirectory . "/bar", 11111);
    }

    public function testGetDir()
    {
        $this->assertEquals(
            array(
                "bar/" => new Entity(11111),
                "foo.txt" => new Entity(12345)
            ),
            $this->fileStorage->getDir("/")->getDirectoryList()
        );
        $this->assertEquals(
            array(
                "foobar.txt" => new Entity(54321)
            ),
            $this->fileStorage->getDir("/bar/")->getDirectoryList()
        );

    }

    public function testGetFile()
    {
        $this->assertEquals("Hello World!", $this->fileStorage->getFile("/foo.txt")->getContent());
        $this->assertEquals("text/plain", $this->fileStorage->getFile("/foo.txt")->getMimeType());
    }

    public function testPutFile()
    {
        $this->assertTrue($this->fileStorage->putFile("/hello.json", '{"hello": "world"}', "application/json"));
    }

    public function testDeleteFile()
    {
        $this->assertTrue($this->fileStorage->deleteFile("/foo.txt"));
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage unable to change to directory
     */
    public function testGetDirOnFile()
    {
        $this->fileStorage->getDir("/foo.txt");
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage path points to directory, not file
     */
    public function testGetFileOnDir()
    {
        $this->fileStorage->getFile("/");
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage unable to read file
     */
    public function testGetFileOnNonExistingFile()
    {
        $this->fileStorage->getFile("/not-there.txt");
    }

    /**
     * @expectedException fkooman\RemoteStorage\FileStorageException
     * @expectedExceptionMessage unable to change to directory
     */
    public function testGetDirOnNonExistingDir()
    {
        $this->fileStorage->getDir("/dir/not/there/");
    }

    public function tearDown()
    {
        $this->recursiveDelete($this->baseDirectory);
    }

    private function recursiveDelete($dirPath)
    {
        foreach (glob($dirPath . '/*') as $file) {
            if (is_dir($file)) {
                $this->recursiveDelete($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}
