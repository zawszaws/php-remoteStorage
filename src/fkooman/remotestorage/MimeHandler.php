<?php

namespace fkooman\remotestorage;

use RestService\Utils\Config;

class MimeHandler
{
    private $_config;

    public function __construct(Config $c)
    {
        $this->_config = $c;
    }

    public function getMimeType($file)
    {
        $f = realpath($file);
        if (FALSE === $f || !is_file($f)) {
            throw new MimeHandlerException("object does not exist or is not a file");
        }
        switch ($this->_config->getValue('mimeHandler')) {
            case "json":
                return $this->_getJsonMimeType($f);
            case "xattr":
                return $this->_getXattrMimeType($f);
            default:
                throw new MimeHandlerException("invalid handler type");
        }
    }

    public function setMimeType($file, $mimeType)
    {
        $f = realpath($file);
        if (FALSE === $f || !is_file($f)) {
            throw new MimeHandlerException("object does not exist or is not a file");
        }
        switch ($this->_config->getValue('mimeHandler')) {
            case "json":
                return $this->_setJsonMimeType($f, $mimeType);
            case "xattr":
                return $this->_setXattrMimeType($f, $mimeType);
            default:
                throw new MimeHandlerException("invalid handler type");
        }
    }

    private function _setJsonMimeType($file, $mimeType)
    {
        $mimeDb = $this->_config->getValue('filesDirectory') . DIRECTORY_SEPARATOR . "mimedb.json";
        if (!file_exists($mimeDb)) {
            $mimeData = array();
        } else {
            $mimeData = json_decode(file_get_contents($mimeDb), TRUE);
        }
        $mimeData[$file] = $mimeType;
        file_put_contents($mimeDb, json_encode($mimeData));
    }

    private function _setXattrMimeType($file, $mimeType)
    {
        if (!function_exists("xattr_set")) {
            throw new MimeHandlerException("xattr extension is not installed");
        }
        $result = xattr_set($file, 'mime_type', $mimeType);
        if (FALSE === $result) {
            throw new MimeHandlerException("unable to set mime type for this file");
        }
    }

    private function _getJsonMimeType($file)
    {
        $mimeDb = $this->_config->getValue('filesDirectory') . DIRECTORY_SEPARATOR . "mimedb.json";
        if (!file_exists($mimeDb)) {
            $mimeData = array();
        } else {
            $mimeData = json_decode(file_get_contents($mimeDb), TRUE);
        }
        if (!array_key_exists($file, $mimeData)) {
            throw new MimeHandlerException("unable to determine mime type for this file");
        }

        return $mimeData[$file];
    }

    private function _getXattrMimeType($file)
    {
        if (!function_exists("xattr_get")) {
            throw new MimeHandlerException("xattr extension is not installed");
        }
        $mimeType = xattr_get($file, 'mime_type');
        if (FALSE === $mimeType) {
            throw new MimeHandlerException("unable to determine mime type for this file");
        }

        return $mimeType;
    }

}
