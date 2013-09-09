<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Document;

class FolderTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleFolder()
    {
        $folder = new Folder(
            array(
                "foo.txt" => new Document("Hello World!", "text/plain", 3),
                "bar.txt" => new Document("{}", "application/json", 44),
                "foo/" => new Folder(
                    array(
                        "foo.txt" => new Document("Hello World!", "text/plain", 3),
                        "bar.txt" => new Document("{}", "application/json", 44)
                    ),
                    4
                )
            ),
            5
        );
        $this->assertEquals(
            array(
                'foo.txt' => 3,
                'bar.txt' => 44,
                'foo/' => 4
            ),
            $folder->getFlatFolderList()
        );
    }
}
