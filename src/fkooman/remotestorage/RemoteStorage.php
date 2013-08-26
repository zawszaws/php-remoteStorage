<?php

namespace fkooman\remotestorage;

use fkooman\oauth\rs\TokenIntrospection;

/**
 * Validate the path Request with the actual OAuth authorization information
 * And request stuff from the File backend if validate matches
 */
class RemoteStorage
{
    private $storageBackend;
    private $tokenIntrospection;

    public function __construct(StorageInterface $storageBackend, TokenIntrospection $tokenIntrospection = null)
    {
        $this->storageBackend = $storageBackend;
        $this->tokenIntrospection = $tokenIntrospection;
    }

    public function getDir($dirPath)
    {
        // always require the user to match the directory
        $p = new PathParser($dirPath);
        if ($p->getUserId() !== $this->tokenIntrospection->getSub()) {
            throw new RemoteStorageException("forbidden", "directory does not belong to user");
        }
        $moduleName = $p->getModuleName();

        // always require the scope to match
        $this->requireAnyScope($this->tokenIntrospection->getScope(), array("$moduleName:r", "$moduleName:rw", "root:r", "root:rw"));

        return $this->storageBackend->getDir($dirPath);
    }

    public function getFile($filePath)
    {
        // only require the user to match the directory when not public
        $p = new PathParser($filePath);
        if (!$p->getIsPublic()) {
            if ($p->getUserId() !== $this->tokenIntrospection->getSub()) {
                throw new RemoteStorageException("forbidden", "directory does not belong to user");
            }
            $moduleName = $p->getModuleName();

            // always require the scope to match
            $this->requireAnyScope($this->tokenIntrospection->getScope(), array("$moduleName:r", "$moduleName:rw", "root:r", "root:rw"));
        }

        return $this->storageBackend->getFile($filePath);
    }

    public function putFile($filePath, $fileContent, $fileMimeType)
    {
        // always require the user to match the directory
        $p = new PathParser($filePath);
        if ($p->getUserId() !== $this->tokenIntrospection->getSub()) {
            throw new RemoteStorageException("forbidden", "directory does not belong to user");
        }
        $moduleName = $p->getModuleName();

        // always require the scope to match
        $this->requireAnyScope($this->tokenIntrospection->getScope(), array("$moduleName:rw", "root:rw"));

        return $this->storageBackend->putFile($filePath, $fileContent, $fileMimeType);
    }

    public function deleteFile($filePath)
    {
        // always require the user to match the directory
        $p = new PathParser($filePath);
        if ($p->getUserId() !== $this->tokenIntrospection->getSub()) {
            throw new RemoteStorageException("forbidden", "directory does not belong to user");
        }
        $moduleName = $p->getModuleName();

        // always require the scope to match
        $this->requireAnyScope($this->tokenIntrospection->getScope(), array("$moduleName:rw", "root:rw"));

        return $this->storageBackend->deleteFile($filePath);
    }

    /**
     * Just any of the scopes in $requestedScope should be granted then we are
     * fine
     */
    private function requireAnyScope($grantedScope, array $requestedScope)
    {
        $grantedScopeArray = explode(" ", $grantedScope);
        foreach ($requestedScope as $scope) {
            if (in_array($scope, $grantedScopeArray)) {
                return;
            }
        }
        throw new RemoteStorageException("insufficient_scope", "no permission for this call with granted scope");
    }
}
