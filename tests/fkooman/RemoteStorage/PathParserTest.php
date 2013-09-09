<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\PathParser;
use fkooman\RemoteStorage\PathParserException;

class PathParserTest extends \PHPUnit_Framework_TestCase
{
    public function testPrivateDocument()
    {
        $p = new PathParser("/admin/path/to/Document.txt");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertFalse($p->getIsPublic());
        $this->assertFalse($p->getIsFolder());
    }

    public function testPrivateFolder()
    {
        $p = new PathParser("/admin/path/to/Folder/");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertFalse($p->getIsPublic());
        $this->assertTrue($p->getIsFolder());
    }

    public function testPublicDocument()
    {
        $p = new PathParser("/admin/public/path/to/Document.txt");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertTrue($p->getIsPublic());
        $this->assertFalse($p->getIsFolder());
    }

    public function testPublicFolder()
    {
        $p = new PathParser("/admin/public/path/to/Folder/");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertTrue($p->getIsPublic());
        $this->assertTrue($p->getIsFolder());
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
                $p = new PathParser($t);
            } catch (PathParserException $e) {
                $this->assertTrue(false);
            }
        }
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
            "admin/public/foo.txt"
        );
        foreach ($testPath as $t) {
            try {
                $p = new PathParser($t);
                echo $t . PHP_EOL;
                $this->assertTrue(false);
            } catch (PathParserException $e) {
                // nop
            }
        }
    }
}
