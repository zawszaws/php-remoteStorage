<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\PathParser;
use fkooman\RemoteStorage\PathParserException;

class PathParserTest extends \PHPUnit_Framework_TestCase
{
    public function testPrivateFile()
    {
        $p = new PathParser("/admin/path/to/file.txt");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertFalse($p->getIsPublic());
        $this->assertFalse($p->getIsDirectory());
    }

    public function testPrivateDirectory()
    {
        $p = new PathParser("/admin/path/to/directory/");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertFalse($p->getIsPublic());
        $this->assertTrue($p->getIsDirectory());
    }

    public function testPublicFile()
    {
        $p = new PathParser("/admin/public/path/to/file.txt");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertTrue($p->getIsPublic());
        $this->assertFalse($p->getIsDirectory());
    }

    public function testPublicDirectory()
    {
        $p = new PathParser("/admin/public/path/to/directory/");
        $this->assertEquals("admin", $p->getUserId());
        $this->assertTrue($p->getIsPublic());
        $this->assertTrue($p->getIsDirectory());
    }

    public function testValidPaths()
    {
        $testPath = array(
            "/admin/public/foo/",
            "/admin/foo/",
            "/admin/public/foo/bar.txt",
            "/admin/public/foo/bar/very/long/path/with/file"
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
