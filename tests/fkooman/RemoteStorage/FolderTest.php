<?php

namespace fkooman\RemoteStorage;

class FolderTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleFolder()
    {
        $folder = new Folder(
            array(
                "foo.txt" => 3,
                "bar.txt" => 44,
                "foo/" => 4
            ),
            5
        );
        $this->assertEquals(
            '{"foo.txt":3,"bar.txt":44,"foo\/":4}',
            $folder->getContent()
        );
        $this->assertEquals(5, $folder->getRevisionId());
        $this->assertEquals("application/json", $folder->getMimeType());
    }
}
