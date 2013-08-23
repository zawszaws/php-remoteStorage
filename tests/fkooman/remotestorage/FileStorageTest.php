<?php

require_once 'vendor/autoload.php';

use fkooman\remotestorage\FileStorage;
use RestService\Utils\Config;

class FileStorageTest extends PHPUnit_Framework_TestCase
{
    private $_tmpDir;
    private $_c;

    public function setUp()
    {
        $this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        mkdir($this->_tmpDir);

        // load default config
        $this->_c = new Config(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini.defaults");

        // override DB config in memory only
        $this->_c->setValue("filesDirectory", $this->_tmpDir);
    }

    public function tearDown()
    {
        $this->_rrmdir($this->_tmpDir);
    }

    public function testUploadAndGetFile()
    {
        $f = new FileStorage($this->_c);
        $this->assertTrue($f->putFile("/foo/bar/demo.txt", "Hello World", "text/plain"));
        $this->assertTrue($f->putFile("/foo/bar/test.json", "[]", "application/json"));
        $this->assertTrue($f->putFile("/foo/bar/foobar/foobaz/test.html", "<html></html>", "text/html"));

        $filePath = $f->getFile("/foo/bar/demo.txt", $mimeType);
        $this->assertEquals("Hello World", file_get_contents($filePath));
        $this->assertEquals("text/plain", $mimeType);

        $filePath = $f->getFile("/foo/bar/test.json", $mimeType);
        $this->assertEquals("[]", file_get_contents($filePath));
        $this->assertEquals("application/json", $mimeType);

        $filePath = $f->getFile("/foo/bar/foobar/foobaz/test.html", $mimeType);
        $this->assertEquals("<html></html>", file_get_contents($filePath));
        $this->assertEquals("text/html", $mimeType);

        // FIXME: the time is only correct if the test runs fast enough...
        $this->assertEquals(array("demo.txt" => time(), "test.json" => time(), "foobar/" => time()), $f->getDir("/foo/bar/"));
        $this->assertEquals(array("test.html" => time()), $f->getDir("/foo/bar/foobar/foobaz/"));

        $this->assertTrue($f->deleteFile("/foo/bar/demo.txt"));
        $this->assertTrue($f->deleteFile("/foo/bar/test.json"));
        $this->assertTrue($f->deleteFile("/foo/bar/foobar/foobaz/test.html"));

    }

    public function testGetDirListOnFile()
    {
        $f = new FileStorage($this->_c);
        $this->assertFalse($f->getDir("/foo/bar/demo.txt"));
    }

    public function testFileFromDir()
    {
        $f = new FileStorage($this->_c);
        $this->assertTrue($f->putFile("/foo/bar/demo.txt", "Hello World", "text/plain"));
        $this->assertFalse($f->getFile("/foo/bar/", $mimeType));
    }

    public function testDeleteDir()
    {
        $f = new FileStorage($this->_c);
        $this->assertTrue($f->putFile("/foo/bar/demo.txt", "Hello World", "text/plain"));
        $this->assertFalse($f->deleteFile("/foo/bar/"));
    }

    public function testPutDir()
    {
        $f = new FileStorage($this->_c);
        $this->assertFalse($f->putFile("/foo/bar/demo.txt/", "Hello World", "text/plain"));
    }

    private function _rrmdir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->_rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }

}
