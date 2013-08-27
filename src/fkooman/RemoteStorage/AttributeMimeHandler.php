<?php

namespace fkooman\RemoteStorage;

class AttributeMimeHandler implements MimeHandlerInterface
{
    public function __construct()
    {
        if (!function_exists("xattr_get") || !function_exists("xattr_set")) {
            throw new MimeHandlerException("xattr extension is not installed");
        }
    }

    public function getMimeType($filePath)
    {
        $mimeType = xattr_get($filePath, 'mime_type');
        if (false === $mimeType) {
            throw new MimeHandlerException("unable to determine mime type for this file");
        }

        return $mimeType;
    }

    public function setMimeType($filePath, $mimeType)
    {
        if (false === xattr_set($filePath, 'mime_type', $mimeType)) {
            throw new MimeHandlerException("unable to set mime type for this file");
        }
    }
}
