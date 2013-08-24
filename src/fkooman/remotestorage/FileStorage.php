<?php

namespace fkooman\remotestorage;

use fkooman\Config\Config;

class FileStorage
{
    private $config;

    public function __construct(Config $c)
    {
        $this->config = $c;
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
            return false;
        }
        $entries = array();
        $dir = realpath($this->config->getValue('filesDirectory') . $path);
        if (false !== $dir && is_dir($dir)) {
            $cwd = getcwd();
            if (false === @chdir($dir)) {
                // could not enter directory
                return false;
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
     * @return mixed the full file path on success, or false when
     *                              the file does not exist
     * @throws FileStorageException if the file could not be read
     */
    public function getFile($path, &$mimeType)
    {
        if (strrpos($path, "/") === strlen($path) - 1) {
            return false;
        }
        $filePath = realpath($this->config->getValue('filesDirectory') . $path);
        if (false === $filePath || !is_file($filePath)) {
            return false;
        }

        $m = new MimeHandler($this->config);
        $mimeType = $m->getMimeType($filePath);

        return $filePath;
    }

    /**
     * Store a file
     *
     * @param string $path the relative path from the API root to the
     *                              file, not ending with a "/"
     * @param  string               $fileData the contents of the file to be written
     * @return boolean              true on success, false on failure
     * @throws FileStorageException if a directory needs to be created for
     *                              holding this file and that fails
     */
    public function putFile($path, $fileData, $mimeType)
    {
        if (strrpos($path, "/") === strlen($path) - 1) {
            return false;
        }
        $file = $this->config->getValue('filesDirectory') . $path;
        $directory = dirname($file);
        $dir = realpath($directory);
        if (false === $dir) {
            $this->createDirectory($directory);
            $dir = realpath($directory);
            if (false === $dir) {
                throw new FileStorageException("unable to create directory");
            }
        }
        if (!is_dir($dir)) {
            // parent of file already exists and is not a directory
            return false;
        }

        $result = file_put_contents($file, $fileData);

        $m = new MimeHandler($this->config);
        $m->setMimeType($file, $mimeType);

        return false !== $result;
    }

    /**
     * Delete a file
     *
     * @param string $path the relative path from the API root to the
     *                              file, not ending with a "/"
     * @return boolean              true on success, false on failure
     * @throws FileStorageException if the file could not be deleted, even
     *                              though it exists
     */
    public function deleteFile($path)
    {
        if (strrpos($path, "/") === strlen($path) - 1) {
            return false;
        }
        $file = realpath($this->config->getValue('filesDirectory') . $path);
        if (false === $file || !is_file($file)) {
            return false;
        }

        if (@unlink($file) === false) {
            throw new FileStorageException("unable to delete file");
        }

        return true;
    }

    private function createDirectory($dir)
    {
        if (!file_exists($dir)) {
            if (@mkdir($dir, 0775, true) === false) {
                throw new FileStorageException("unable to create directory");
            }
        }
    }
}
