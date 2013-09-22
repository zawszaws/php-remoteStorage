<?php

namespace fkooman\RemoteStorage;

use fkooman\OAuth\ResourceServer\ResourceServer;
use fkooman\RemoteStorage\Exception\RemoteStorageException;

use fkooman\RemoteStorage\Exception\DocumentException;

/**
 * Validate the path Request with the actual OAuth authorization information
 * And request stuff from the storage backend if validate matches
 */
class RemoteStorage
{
    /** @var fkooman\RemoteStorage\StorageInterface */
    private $storageBackend;

    /** @var fkooman\OAuth\ResourceServer\ResourceServer */
    private $resourceServer;

    public function __construct(StorageInterface $storageBackend, ResourceServer $resourceServer)
    {
        $this->storageBackend = $storageBackend;
        $this->resourceServer = $resourceServer;
    }

    public function getFolder(Path $path, $ifNonMatch)
    {
        $this->requireAuthorization($path, array("r", "rw"));

        $folder = $this->storageBackend->getFolder($path);
        if (null !== $ifNonMatch) {
            if ($ifNonMatch === $folder->getRevisionId()) {
                return null;
            }
        }

        return $folder;
    }

    public function getDocument(Path $path, $ifNonMatch)
    {
        // only require the user to match the folder when not public
        if (!$path->getIsPublic()) {
            $this->requireAuthorization($path, array("r", "rw"));
        }

        $document = $this->storageBackend->getDocument($path);
        if (null !== $ifNonMatch) {
            if ($ifNonMatch === $folder->getRevisionId()) {
                return null;
            }
        }

        return $document;
    }

    public function putDocument(Path $path, Document $document, $ifMatch, $ifNonMatch)
    {
        // always require the user to match the folder
        $this->requireAuthorization($path, array("rw"));

        $doc = false;
        try {
            $doc = $this->storageBackend->getDocument($path);
        } catch (DocumentException $e) {
        }

        if (null !== $ifNonMatch) {
            if ("*" === $ifNonMatch && false !== $doc) {
                throw new RemoteStorageException("precondition_failed", "document already exists");
            }
        }

        if (null !== $ifMatch) {
            if ($doc->getRevisionId() !== $ifMatch) {
                throw new RemoteStorageException("precondition_failed", "existing document has unexpected revision");
            }
        }

        if (false !== $doc) {
            $document->setRevisionId($doc->getRevisionId() + 1);
        }

        $this->storageBackend->putDocument($path, $document);

        return $document;
    }

    public function deleteDocument(Path $path, $ifMatch)
    {
        // always require the user to match the folder
        $this->requireAuthorization($path, array("rw"));

        $document = $this->storageBackend->getDocument($path);
        if (null !== $ifMatch) {
            if ($ifMatch !== $document->getRevisionId()) {
                throw new RemoteStorageException("precondition_failed", "existing document has unexpected revision");
            }
        }

        $this->storageBackend->deleteDocument($path);

        return $document;
    }

    private function requireAuthorization(Path $path, array $requiredScope)
    {
        $tokenIntrospection = $this->resourceServer->verifyToken();

        $userId = $tokenIntrospection->getSub();
        if (false === $userId) {
            throw new RemoteStorageException(
                "internal_server_error",
                "sub information not available from token introspection endpoint"
            );
        }
        $grantedScope = $tokenIntrospection->getScope();
        if (false === $grantedScope) {
            throw new RemoteStorageException(
                "internal_server_error",
                "scope information not available from token introspection endpoint"
            );
        }
        $grantedScopeArray = explode(" ", $grantedScope);

        // always require the user to match the folder
        if ($path->getUserId() !== $userId) {
            throw new RemoteStorageException("forbidden", "path needs to be owned by user making the request");
        }
        $moduleName = $path->getModuleName();

        $requiredModuleScope = array();
        foreach ($requiredScope as $s) {
            $requiredModuleScope[] = sprintf("%s:%s", $moduleName, $s);
            $requiredModuleScope[] = sprintf("root:%s", $s);
        }
        // always require the scope to match
        $this->requireAnyScope($grantedScopeArray, $requiredModuleScope);
    }

    /**
     * Just any of the scopes in $requestedScope should be granted then we are
     * fine
     */
    private function requireAnyScope(array $grantedScopeArray, array $requiredModuleScope)
    {
        foreach ($requiredModuleScope as $scope) {
            if (in_array($scope, $grantedScopeArray)) {
                return;
            }
        }
        throw new RemoteStorageException(
            "insufficient_scope",
            "no permission for this call with granted scope"
        );
    }
}
