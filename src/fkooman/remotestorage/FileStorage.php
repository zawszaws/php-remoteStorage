<?php

namespace fkooman\remotestorage;

use RestService\Utils\Config;
use RestService\Utils\Logger;

class FileStorage
{
    private $_config;
    private $_logger;

    public function __construct(Config $c, Logger $l = NULL)
    {
        $this->_config = $c;
        $this->_logger = $l;
    }

    /**
     * Get the directory contents
     *
     * @param string $path the relative path from the API root to the directory,
     *                     ending with a "/"
     * @return array an array with all files in the directory as key and
     *                     the last modified time as value, empty when
     *                     directory does not exist or contains no files
     */
    public function getDir($path)
    {
        if (strrpos($path, "/") !== strlen($path) - 1) {
            return FALSE;
        }
        $entries = array();
        $dir = realpath($this->_config->getValue('filesDirectory') . $path);
        if (FALSE !== $dir && is_dir($dir)) {
            $cwd = getcwd();
            if (FALSE === @chdir($dir)) {
                // could not enter directory
                return FALSE;
            }
            foreach (glob("*", GLOB_MARK) as $e) {
                $entries[$e] = filemtime($e);
            }
            chdir($cwd);
        }

        return $entries;
    }

    /**
     * Get a specific file
     *
     * @param string $path the relative path from the API root to the
     *                              file, not ending with a "/"
     * @return mixed the full file path on success, or FALSE when
     *                              the file does not exist
     * @throws FileStorageException if the file could not be read
     */
    public function getFile($path, &$mimeType)
    {
        if (strrpos($path, "/") === strlen($path) - 1) {
            return FALSE;
        }
        $filePath = realpath($this->_config->getValue('filesDirectory') . $path);
        if (FALSE === $filePath || !is_file($filePath)) {
            return FALSE;
        }

        $m = new MimeHandler($this->_config);
        $mimeType = $m->getMimeType($filePath);

        return $filePath;
    }

    /**
     * Store a file
     *
     * @param string $path the relative path from the API root to the
     *                              file, not ending with a "/"
     * @param  string               $fileData the contents of the file to be written
     * @return boolean              TRUE on success, FALSE on failure
     * @throws FileStorageException if a directory needs to be created for
     *                              holding this file and that fails
     */
    public function putFile($path, $fileData, $mimeType)
    {
        if (strrpos($path, "/") === strlen($path) - 1) {
            return FALSE;
        }
        $file = $this->_config->getValue('filesDirectory') . $path;
        $directory = dirname($file);
        $dir = realpath($directory);
        if (FALSE === $dir) {
            $this->_createDirectory($directory);
            $dir = realpath($directory);
            if (FALSE === $dir) {
                throw new FileStorageException("unable to create directory");
            }
        }
        if (!is_dir($dir)) {
            // parent of file already exists and is not a directory
            return FALSE;
        }

        $result = file_put_contents($file, $fileData);

        $m = new MimeHandler($this->_config);
        $m->setMimeType($file, $mimeType);

        return FALSE !== $result;
    }

    /**
     * Delete a file
     *
     * @param string $path the relative path from the API root to the
     *                              file, not ending with a "/"
     * @return boolean              TRUE on success, FALSE on failure
     * @throws FileStorageException if the file could not be deleted, even
     *                              though it exists
     */
    public function deleteFile($path)
    {
        if (strrpos($path, "/") === strlen($path) - 1) {
            return FALSE;
        }
        $file = realpath($this->_config->getValue('filesDirectory') . $path);
        if (FALSE === $file || !is_file($file)) {
            return FALSE;
        }

        if (@unlink($file) === FALSE) {
            throw new FileStorageException("unable to delete file");
        }

        return TRUE;
    }

    private function _createDirectory($dir)
    {
        if (!file_exists($dir)) {
            if (@mkdir($dir, 0775, TRUE) === FALSE) {
                throw new FileStorageException("unable to create directory");
            }
        }
    }

}
