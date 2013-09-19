<?php

namespace fkooman\RemoteStorage\Exception;

class RemoteStorageException extends \Exception
{
    private $description;

    public function __construct($message, $description, $code = 0, \Exception $previous = null)
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
            case "precondition_failed":
                return 412;
            case "not_found":
                return 404;
            case "forbidden":
                return 403;
            default:
                return 400;
        }
    }
}
