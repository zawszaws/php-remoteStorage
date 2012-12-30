<?php

namespace RemoteStorage;

use \RestService\Utils\Config as Config;
use \RestService\Utils\Logger as Logger;
use \RestService\Http\HttpResponse as HttpResponse;

class RemoteStorage
{
    private $_c;
    private $_l;

    public function __construct(Config $c, Logger $l)
    {
        $this->_c = $c;
        $this->_l = $l;
    }

    public function getDir($path)
    {
        $response = new HttpResponse(200, "application/json");

        $entries = array();
        $dir = realpath($this->_c->getValue('filesDirectory') . $path);
        if (FALSE !== $dir && is_dir($dir)) {
            $cwd = getcwd();
            chdir($dir);
            foreach (glob("*", GLOB_MARK) as $e) {
                $entries[$e] = filemtime($e);
            }
            chdir($cwd);
        }
        $response->setContent(json_encode($entries, JSON_FORCE_OBJECT));

        return $response;
    }

    public function getFile($path)
    {
        $this->_l->logDebug("getting relative file path: " . $path);

        $file = realpath($this->_c->getValue('filesDirectory') . $path);
        if (FALSE === $file || !is_file($file)) {
            throw new RemoteStorageException("not_found", "file not found");
        }
        if (function_exists("xattr_get")) {
            $mimeType = xattr_get($file, 'mime_type');
        } else {
            $mimeType = "application/json";
        }
        $response = new HttpResponse(200, $mimeType);

        $this->_l->logDebug("getting abs file path: " . $file);
        $response->setContent(file_get_contents($file));

        return $response;
    }

    public function putFile($path, $fileData, $contentType = NULL)
    {
        $response = new HttpResponse(200);

        //if ($this->_request->isDirectoryRequest()) {
        //    throw new RemoteStorageException("invalid_request", "cannot store a directory");
        //}

        $file = $this->_c->getValue('filesDirectory') . $path;
        $directory = dirname($file);
        $dir = realpath($directory);
        if (FALSE === $dir) {
            $this->_createDirectory($directory);
            $dir = realpath($directory);
            if (FALSE === $dir) {
                throw new RemoteStorageException("invalid_request", "unable to create directory");
            }
        }
        if (!is_dir($dir)) {
            throw new RemoteStorageException("invalid_request", "parent of file already exists and is not a directory");
        }

        /* XXX we should better lock that file here */
        //$etag = file_exists($file) ? $this->_getETag($file) : NULL;
        //if ($this->_doIfMatchChecks($etag, $response)) {
        //    return $response;
        //}

        //$contentType = $this->_request->getHeader("Content-Type");
        if (NULL === $contentType) {
            $contentType = "application/json";
        }
        file_put_contents($file, $fileData);
        // store mime_type
        if (function_exists("xattr_set")) {
            xattr_set($file, 'mime_type', $contentType);
        }

        return $response;
    }

    public function deleteFile($path)
    {
        $response = new HttpResponse(200);

        //if ($this->_request->isDirectoryRequest()) {
        //    throw new RemoteStorageException("invalid_request", "directories cannot be deleted");
        //}

        $file = realpath($this->_c->getValue('filesDirectory') . $path);
        if (FALSE === $file || !is_file($file)) {
            throw new RemoteStorageException("not_found", "file not found");
        }

        /* XXX we should better lock that file here */
        //$etag = $this->_getETag($file);
        //if ($this->_doIfMatchChecks($etag, $response)) {
        //    return $response;
        // }

        if (@unlink($file) === FALSE) {
            throw new RemoteStorageException("internal_server_error", "unable to delete file");
        }

        return $response;
    }

#    private function _getETag($file)
#    {
#        $fs = stat($file);

#        return sprintf('"%x-%x-%s"', $fs['ino'], $fs['size'], base_convert(str_pad($fs['mtime'], 16, "0"), 10, 16));
#    }

    /* supply NULL for $etag if file is not present */
#    private function _doIfMatchChecks($etag, &$response)
#    {
#        /* XXX better use an exception? */
#        if (Null !== $this->_request->getHeader("If-Match")) {
#            /* XXX the client could specify multiple ETags separated by comma */
#            $match = $this->_request->getHeader("If-Match");
#            if (($match === '*' && $etag !== NULL) ||
#                        ($match !== '*' && $match === $etag)) {
#                return FALSE;
#            }
#            $response->setStatusCode("412");

#            return TRUE;
#        } elseif (NULL !== $this->_request->getHeader("If-None-Match")) {
#            /* XXX the client could specify multiple ETags separated by comma */
#            $match = $this->_request->getHeader("If-None-Match");
#            if (($match === '*' && $etag === NULL) ||
#                    ($match !== '*' && $match !== $etag)) {
#                return FALSE;
#            }
#            $method = $this->_request->getRequestMethod();
#            if ($method === 'HEAD' || $method === 'GET') {
#                $response->setStatusCode('304');
#            } else {
#                $response->setStatusCode('412');
#            }

#            return TRUE;
#        } else {
#            return FALSE;
#        }
#    }

    private function _createDirectory($dir)
    {
        if (!file_exists($dir)) {
            if (@mkdir($dir, 0775, TRUE) === FALSE) {
                throw new Exception("unable to create directory");
            }
        }
    }

}
