<?php

require_once 'lib/_autoload.php';

use \RemoteStorage\RemoteStorage as RemoteStorage;
use \RestService\Utils\Config as Config;
use \RestService\Http\HttpRequest as HttpRequest;

class RemoteStorageTest extends PHPUnit_Framework_TestCase
{
    private $_tmpDir;
    private $_c;

    public function setUp()
    {
        $this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        mkdir($this->_tmpDir);
        // we need to add some initial files to the directory...
        // create some module directories
        $modules = array("contacts", "calendar", "money", "music", "video");
        $users = array ("admin", "teacher", "bmcatee");
        foreach ($users as $u) {
            foreach ($modules as $m) {
                $privateDir = $this->_tmpDir . DIRECTORY_SEPARATOR . $u . DIRECTORY_SEPARATOR . $m;
                $publicDir = $this->_tmpDir . DIRECTORY_SEPARATOR . $u . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . $m;
                mkdir($privateDir, 0775, TRUE);
                mkdir($publicDir, 0775, TRUE);
                // add some files to it
                for ($i = 0; $i < 5; $i++) {
                    file_put_contents($privateDir . DIRECTORY_SEPARATOR . $i.".json", json_encode(array("a", "b", "c", "d")));
                    file_put_contents($publicDir . DIRECTORY_SEPARATOR . $i.".json", json_encode(array("a", "b", "c", "d")));
                }
            }
        }

        // load default config
        $this->_c = new Config(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini.defaults");

        // override DB config in memory only
        $this->_c->setValue("filesDirectory", $this->_tmpDir);
        // point to a mock file instead of a "real" URL
        $tokenInfoFile = "file://" . __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "tokeninfo.json";
        error_log($tokenInfoFile);
        $this->_c->setSectionValue("OAuth", "tokenInfoEndpoint", $tokenInfoFile);
    }

    public function tearDown()
    {
        $this->_rrmdir($this->_tmpDir);
    }

    public function testDownloadPublicFile()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/money/1.json");
        $r = new RemoteStorage($this->_c, NULL);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["a","b","c","d"]', $response->getContent());
    }

#    public function testDirList()
#    {
#        $h = new RemoteStorageRequest("https://www.example.org/api.php");
#        $h->setPathInfo("/dir/list/");
#        $r = new RemoteStorage($this->_c, $h);
#        $response = $r->getDir($h->getPathInfo());
#        $this->assertEquals("{}", $response->getContent());
#    }

#    public function testFlow()
#    {
#        // add file
#        $h = new RemoteStorageRequest("https://www.example.org/api.php", "PUT");
#        $h->setPathInfo("/dir/list/foo");
#        $h->setContent("Hello World");
#        $r = new RemoteStorage($this->_c, $h);
#        $response = $r->putFile($h->getPathInfo());
#        $this->assertEquals(200, $response->getStatusCode());

#        // get dir listing
#        $h = new RemoteStorageRequest("https://www.example.org/api.php");
#        $h->setPathInfo("/dir/list/");
#        $r = new RemoteStorage($this->_c, $h);
#        $response = $r->getDir($h->getPathInfo());
#        $this->assertRegExp('{"foo":[0-9]+}', $response->getContent()); // FIXME: regexp is not correct, should be greedy or something!

#        // get file
#        $h = new RemoteStorageRequest("https://www.example.org/api.php", "GET");
#        $h->setPathInfo("/dir/list/foo");
#        $r = new RemoteStorage($this->_c, $h);
#        $response = $r->getFile($h->getPathInfo());
#        $this->assertEquals("Hello World", $response->getContent());

#        // delete file
#        $h = new RemoteStorageRequest("https://www.example.org/api.php", "DELETE");
#        $h->setPathInfo("/dir/list/foo");
#        $r = new RemoteStorage($this->_c, $h);
#        $response = $r->deleteFile($h->getPathInfo());
#        $this->assertEquals(200, $response->getStatusCode());
#    }

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
