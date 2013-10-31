<?php

namespace fkooman\RemoteStorage;

interface StorageInterface
{
    /**
     * Get a folder
     *
     * @return Folder
     * @throws fkooman\RemoteStorage\Exception\FolderException
     *         if the folder does not exist
     *         if the folder is a document
     */
    public function getFolder(Path $path);

    /**
     * Get a document
     *
     * @return Document
     * @throws fkooman\RemoteStorage\Exception\DocumentException
     *         if the document does not exist
     *         if the document is a folder
     */
    public function getDocument(Path $path);

    /**
     * Put a document
     *
     * @return true|false
     * @throws fkooman\RemoteStorage\Exception\DocumentException
     *         if the document points to an existing folder
     */
    public function putDocument(Path $document, Document $document);

    /**
     * Delete a document
     *
     * @return true|false
     * @throws fkooman\RemoteStorage\Exception\DocumentException
     *         if the document points to a folder
     *         if the document does not exist
     */
    public function deleteDocument(Path $document);
}
