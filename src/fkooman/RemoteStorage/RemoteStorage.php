<?php

namespace fkooman\RemoteStorage;

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

    public function getDir(PathParser $pathParser)
    {
        $this->requireAuthorization($pathParser, array("r", "rw"));

        return $this->storageBackend->getDir($pathParser->getEntityPath());
    }

    public function getFile(PathParser $pathParser)
    {
        // only require the user to match the directory when not public
        if (!$pathParser->getIsPublic()) {
            $this->requireAuthorization($pathParser, array("r", "rw"));
        }

        return $this->storageBackend->getFile($pathParser->getEntityPath());
    }

    public function putFile(PathParser $pathParser, $fileContent, $fileMimeType)
    {
        // always require the user to match the directory
        $this->requireAuthorization($pathParser, array("rw"));

        return $this->storageBackend->putFile($pathParser->getEntityPath(), $fileContent, $fileMimeType);
    }

    public function deleteFile(PathParser $pathParser)
    {
        // always require the user to match the directory
        $this->requireAuthorization($pathParser, array("rw"));

        return $this->storageBackend->deleteFile($pathParser->getEntityPath());
    }

    private function requireAuthorization(PathParser $pathParser, array $scopes)
    {
        // always require the user to match the directory
        if ($pathParser->getUserId() !== $this->tokenIntrospection->getSub()) {
            throw new RemoteStorageException("forbidden", "path needs to be owned by user making the request");
        }
        $moduleName = $pathParser->getModuleName();

        $specificScopes = array();
        foreach ($scopes as $s) {
            $specificScopes[] = sprintf("%s:%s", $moduleName, $s);
            $specificScopes[] = sprintf("root:%s", $s);
        }
        // always require the scope to match
        $this->requireAnyScope($this->tokenIntrospection->getScope(), $specificScopes);
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
