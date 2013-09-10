<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Path;
use fkooman\RemoteStorage\Exception\PathException;

class PathTest extends \PHPUnit_Framework_TestCase
{
    public function testPrivateDocument()
    {
        $p = new Path("/admin/path/to/Document.txt");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertFalse($p->getIsPublic());
        $this->assertFalse($p->getIsFolder());
        $this->assertEquals("/admin/path/to/Document.txt", $p->getPath());
        $this->assertEquals("/admin/path/to/", $p->getParentFolder());
    }

    public function testPrivateFolder()
    {
        $p = new Path("/admin/path/to/Folder/");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertFalse($p->getIsPublic());
        $this->assertTrue($p->getIsFolder());
        $this->assertEquals("/admin/path/to/Folder/", $p->getPath());
        $this->assertEquals("/admin/path/to/", $p->getParentFolder());
        $this->assertEquals("path", $p->getModuleName());
    }

    public function testPublicDocument()
    {
        $p = new Path("/admin/public/path/to/Document.txt");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertTrue($p->getIsPublic());
        $this->assertFalse($p->getIsFolder());
    }

    public function testPublicFolder()
    {
        $p = new Path("/admin/public/path/to/Folder/");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertTrue($p->getIsPublic());
        $this->assertTrue($p->getIsFolder());
        $this->assertFalse($p->getIsDocument());
        $this->assertEquals("path", $p->getModuleName());
    }

    public function testParentFolderOnPrivateModuleRoot()
    {
        $p = new Path("/admin/module/");
        $this->assertFalse($p->getParentFolder());
    }

    public function testParentFolderOnPublicModuleRoot()
    {
        $p = new Path("/admin/public/module/");
        $this->assertFalse($p->getParentFolder());
    }

    public function testParentFolderForDocument()
    {
        $p = new Path("/admin/module/foo.txt");
        $this->assertEquals("/admin/module/", $p->getParentFolder());
    }

    public function testParentFolderForPublicDocument()
    {
        $p = new Path("/admin/public/module/foo.txt");
        $this->assertEquals("/admin/public/module/", $p->getParentFolder());
    }

    public function testParentFolderForFolder()
    {
        $p = new Path("/admin/module/bar/");
        $this->assertEquals("/admin/module/", $p->getParentFolder());
    }

    public function testValidPaths()
    {
        $testPath = array(
            "/admin/public/foo/",
            "/admin/foo/",
            "/admin/public/foo/bar.txt",
            "/admin/public/foo/bar/very/long/path/with/Document"
        );
        foreach ($testPath as $t) {
            try {
                $p = new Path($t);
                $this->assertTrue(true);
            } catch (PathException $e) {
                $this->assertTrue(false);
            }
        }
    }

    /**
     * @expectedException \fkooman\RemoteStorage\Exception\PathException
     * @expectedExceptionMessage path must be a string
     */
    public function testNonStringPath()
    {
        $p = new Path(123);
    }

    public function testInvalidPaths()
    {
        $testPath = array(
            "/",
            "/admin",
            "/admin/",
            "/admin/public",
            "/admin/public/",
            "/admin/foo",
            "/admin/public/foo",
            "///",
            "/admin/foo//bar/",
            "admin/public/foo.txt"
        );
        foreach ($testPath as $t) {
            try {
                $p = new Path($t);
                echo $t . PHP_EOL;
                $this->assertTrue(false);
            } catch (PathException $e) {
                $this->assertTrue(true);
            }
        }
    }
}
