<?php

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Exception\RemoteStorageException;
use fkooman\oauth\rs\TokenIntrospection;

/**
 * Validate the path Request with the actual OAuth authorization information
 * And request stuff from the storage backend if validate matches
 */
class RemoteStorage implements StorageInterface
{
    /** @var fkooman\RemoteStorage\StorageInterface */
    private $storageBackend;

    /** @var fkooman\oauth\rs\TokenIntrospection */
    private $tokenIntrospection;

    public function __construct(StorageInterface $storageBackend, TokenIntrospection $tokenIntrospection)
    {
        $this->storageBackend = $storageBackend;
        $this->tokenIntrospection = $tokenIntrospection;
    }

    public function getFolder(Path $path)
    {
        $this->requireAuthorization($path, array("r", "rw"));

        return $this->storageBackend->getFolder($path);
    }

    public function getDocument(Path $path)
    {
        // only require the user to match the folder when not public
        if (!$path->getIsPublic()) {
            $this->requireAuthorization($path, array("r", "rw"));
        }

        return $this->storageBackend->getDocument($path);
    }

    public function putDocument(Path $path, Document $document)
    {
        // always require the user to match the folder
        $this->requireAuthorization($path, array("rw"));

        return $this->storageBackend->putDocument($path, $document);
    }

    public function deleteDocument(Path $path)
    {
        // always require the user to match the folder
        $this->requireAuthorization($path, array("rw"));

        return $this->storageBackend->deleteDocument($path);
    }

    private function requireAuthorization(Path $path, array $scopes)
    {
        $sub = $this->tokenIntrospection->getSub();
        if (false === $sub) {
            throw new RemoteStorageException(
                "internal_server_error",
                "sub information not available from token introspection endpoint"
            );
        }

        // always require the user to match the folder
        if ($path->getUserId() !== $sub) {
            throw new RemoteStorageException("forbidden", "path needs to be owned by user making the request");
        }
        $moduleName = $path->getModuleName();

        $specificScopes = array();
        foreach ($scopes as $s) {
            $specificScopes[] = sprintf("%s:%s", $moduleName, $s);
            $specificScopes[] = sprintf("root:%s", $s);
        }
        // always require the scope to match
        $this->requireAnyScope($specificScopes);
    }

    /**
     * Just any of the scopes in $requestedScope should be granted then we are
     * fine
     */
    private function requireAnyScope(array $requestedScope)
    {
        $grantedScope = $this->tokenIntrospection->getScope();
        if (false === $grantedScope) {
            throw new RemoteStorageException(
                "internal_server_error",
                "scope information not available from token introspection endpoint"
            );
        }
        $grantedScopeArray = explode(" ", $grantedScope);
        foreach ($requestedScope as $scope) {
            if (in_array($scope, $grantedScopeArray)) {
                return;
            }
        }
        throw new RemoteStorageException("insufficient_scope", "no permission for this call with granted scope");
    }
}
