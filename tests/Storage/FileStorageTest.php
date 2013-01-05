<?php

require_once 'lib/_autoload.php';

use \RemoteStorage\FileStorage as FileStorage;
use \RestService\Utils\Config as Config;

class FileStorageTest extends PHPUnit_Framework_TestCase
{
    private $_tmpDir;
    private $_c;

    public function setUp()
    {
        $this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        mkdir($this->_tmpDir);

        // load default config
        $this->_c = new Config(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini.defaults");

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
        $this->assertTrue($f->putFile("/foo/bar/demo.txt", "Hello World"));
        $this->assertTrue($f->putFile("/foo/bar/test.txt", "Hello Test"));
        $this->assertTrue($f->putFile("/foo/bar/foobar/foobaz/test.txt", "Hello Foo"));
        $this->assertEquals("Hello World", $f->getFile("/foo/bar/demo.txt"));
        $this->assertEquals("Hello Test", $f->getFile("/foo/bar/test.txt"));
        $this->assertEquals("Hello Foo", $f->getFile("/foo/bar/foobar/foobaz/test.txt"));
        // FIXME: the time is only correct if the test runs fast enough...
        $this->assertEquals(array("demo.txt" => time(), "test.txt" => time(), "foobar/" => time()), $f->getDir("/foo/bar/"));
        $this->assertEquals(array("test.txt" => time()), $f->getDir("/foo/bar/foobar/foobaz/"));
        $this->assertTrue($f->deleteFile("/foo/bar/demo.txt"));
    }

    public function testGetDirListOnFile()
    {
        $f = new FileStorage($this->_c);
        $this->assertFalse($f->getDir("/foo/bar/demo.txt"));
    }

    public function testFileFromDir()
    {
        $f = new FileStorage($this->_c);
        $this->assertTrue($f->putFile("/foo/bar/demo.txt", "Hello World"));
        $this->assertFalse($f->getFile("/foo/bar/"));
    }

    public function testDeleteDir()
    {
        $f = new FileStorage($this->_c);
        $this->assertTrue($f->putFile("/foo/bar/demo.txt", "Hello World"));
        $this->assertFalse($f->deleteFile("/foo/bar/"));
    }

    public function testPutDir()
    {
        $f = new FileStorage($this->_c);
        $this->assertFalse($f->putFile("/foo/bar/demo.txt/", "Hello World"));
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
