<?php

namespace fkooman\RemoteStorage;

class RemoteStorageException extends \Exception
{
    private $description;

    public function __construct($message, $description, $code = 0, Exception $previous = null)
    {
        $this->description = $description;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getStatusCode()
    {
        switch ($this->message) {
            case "not_found":
                return 404;
            case "invalid_request":
                return 400;
            case "forbidden":
                return 403;
            case "method_not_allowed":
                return 405;
            case "internal_server_error":
                return 500;
            default:
                return 400;
        }
    }
}
