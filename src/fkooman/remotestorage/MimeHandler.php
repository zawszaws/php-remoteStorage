<?php

namespace fkooman\remotestorage;

use fkooman\Config\Config;

class MimeHandler
{
    private $config;

    public function __construct(Config $c)
    {
        $this->config = $c;
    }

    public function getMimeType($file)
    {
        $f = realpath($file);
        if (false === $f || !is_file($f)) {
            throw new MimeHandlerException("object does not exist or is not a file");
        }
        switch ($this->config->getValue('mimeHandler')) {
            case "json":
                return $this->getJsonMimeType($f);
            case "xattr":
                return $this->getXattrMimeType($f);
            default:
                throw new MimeHandlerException("invalid handler type");
        }
    }

    public function setMimeType($file, $mimeType)
    {
        $f = realpath($file);
        if (false === $f || !is_file($f)) {
            throw new MimeHandlerException("object does not exist or is not a file");
        }
        switch ($this->config->getValue('mimeHandler')) {
            case "json":
                return $this->setJsonMimeType($f, $mimeType);
            case "xattr":
                return $this->setXattrMimeType($f, $mimeType);
            default:
                throw new MimeHandlerException("invalid handler type");
        }
    }

    private function setJsonMimeType($file, $mimeType)
    {
        $mimeDb = $this->config->getValue('filesDirectory') . DIRECTORY_SEPARATOR . "mimedb.json";
        if (!file_exists($mimeDb)) {
            $mimeData = array();
        } else {
            $mimeData = json_decode(file_get_contents($mimeDb), true);
        }
        $mimeData[$file] = $mimeType;
        file_put_contents($mimeDb, json_encode($mimeData));
    }

    private function setXattrMimeType($file, $mimeType)
    {
        if (!function_exists("xattr_set")) {
            throw new MimeHandlerException("xattr extension is not installed");
        }
        $result = xattr_set($file, 'mime_type', $mimeType);
        if (false === $result) {
            throw new MimeHandlerException("unable to set mime type for this file");
        }
    }

    private function getJsonMimeType($file)
    {
        $mimeDb = $this->config->getValue('filesDirectory') . DIRECTORY_SEPARATOR . "mimedb.json";
        if (!file_exists($mimeDb)) {
            $mimeData = array();
        } else {
            $mimeData = json_decode(file_get_contents($mimeDb), true);
        }
        if (!array_key_exists($file, $mimeData)) {
            throw new MimeHandlerException("unable to determine mime type for this file");
        }

        return $mimeData[$file];
    }

    private function getXattrMimeType($file)
    {
        if (!function_exists("xattr_get")) {
            throw new MimeHandlerException("xattr extension is not installed");
        }
        $mimeType = xattr_get($file, 'mime_type');
        if (false === $mimeType) {
            throw new MimeHandlerException("unable to determine mime type for this file");
        }

        return $mimeType;
    }
}
