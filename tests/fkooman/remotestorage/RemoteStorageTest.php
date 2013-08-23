<?php

require_once 'vendor/autoload.php';

use fkooman\remotestorage\RemoteStorage;
use fkooman\remotestorage\MimeHandler;

use fkooman\Config\Config;
use RestService\Http\HttpRequest;

class RemoteStorageTest extends PHPUnit_Framework_TestCase
{
    private $_tmpDir;
    private $_c;
    private $_client;

    public function setUp()
    {

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(200, null, '{"active": true,"client_id": "debug_client","scope": "money:r money:rw","sub": "admin"}'));
        $this->_client = new Guzzle\Http\Client("https://auth.example.org/introspect");
        $this->_client->addSubscriber($plugin);

        $this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "remoteStorage_" . rand();
        mkdir($this->_tmpDir);

        // load default config
        $this->_c = Config::fromIniFile(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remoteStorage.ini.defaults");

        $configData = $this->_c->toArray();
        $configData["filesDirectory"] = $this->_tmpDir;

        $this->_c = new Config($configData);

        // we need to add some initial files to the directory...
        // create some module directories
        $modules = array("contacts", "calendar", "money", "music", "video");
        $users = array ("admin", "teacher", "bmcatee");

        $mime = new MimeHandler($this->_c);

        foreach ($users as $u) {
            foreach ($modules as $m) {
                $privateDir = $this->_tmpDir . DIRECTORY_SEPARATOR . $u . DIRECTORY_SEPARATOR . $m;
                $publicDir = $this->_tmpDir . DIRECTORY_SEPARATOR . $u . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . $m;
                mkdir($privateDir, 0775, TRUE);
                mkdir($publicDir, 0775, TRUE);
                // add some files to it
                for ($i = 0; $i < 5; $i++) {
                    $privateFile = $privateDir . DIRECTORY_SEPARATOR . $i.".json";
                    file_put_contents($privateFile, json_encode(array("a", "b", "c", "d")));
                    $mime->setMimeType($privateFile, "application/json");
                    $publicFile = $publicDir . DIRECTORY_SEPARATOR . $i.".json";
                    file_put_contents($publicFile, json_encode(array("a", "b", "c", "d")));
                    $mime->setMimeType($publicFile, "application/json");
                }
                $privateSubDir = $privateDir . DIRECTORY_SEPARATOR . "sub";
                mkdir($privateSubDir, 0775, TRUE);
                for ($i = 0; $i < 5; $i++) {
                    $privateSubDirFile = $privateSubDir . DIRECTORY_SEPARATOR . $i.".json";
                    file_put_contents($privateSubDirFile, json_encode(array("a", "b", "c", "d")));
                    $mime->setMimeType($privateSubDirFile, "application/json");
                }
            }
        }
    }

    public function tearDown()
    {
        $this->_rrmdir($this->_tmpDir);
    }

    public function testDownloadPublicFile()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/money/1.json");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["a","b","c","d"]', file_get_contents($response->getContentFile()));
    }

    public function testListPublicFiles()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/money/");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"error":"no_token","error_description":"missing token"}', $response->getContent());
        $this->assertEquals('Bearer realm="remoteStorage Server"', $response->getHeader("WWW-Authenticate"));
    }

    public function testPrivateUploadFile()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "PUT");
        $h->setPathInfo("/admin/public/money/my.txt");
        $h->setHeader("Content-Type", "text/plain");
        $h->setContent("Hello World!");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPrivateDeleteFile()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "DELETE");
        $h->setPathInfo("/admin/public/money/1.json");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testListPrivateFiles()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/money/");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('{"0.json":[0-9]+,"1.json":[0-9]+,"2.json":[0-9]+,"3.json":[0-9]+,"4.json":[0-9]+}', $response->getContent());
    }

    public function testListPrivateFilesInWrongModule()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/calendar/");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"error":"insufficient_scope","error_description":"no permission for this call with granted scope"}', $response->getContent());
    }

    public function testListPrivateFilesFromSomeoneElse()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/teacher/public/calendar/");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"error":"forbidden","error_description":"authorized user does not match user in path"}', $response->getContent());
    }

    public function testPrivateSubDirList()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/money/sub/");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode(array("0.json"=>time(), "1.json"=>time(), "2.json"=>time(), "3.json"=>time(), "4.json"=>time())), $response->getContent());
    }

    public function testDownloadPrivateFileSubDir()
    {
        $h = new HttpRequest("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/money/sub/1.json");
        $h->setHeader("Authorization", "Bearer foo");
        $r = new RemoteStorage($this->_c, NULL, $this->_client);
        $response = $r->handleRequest($h);
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["a","b","c","d"]', file_get_contents($response->getContentFile()));
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
