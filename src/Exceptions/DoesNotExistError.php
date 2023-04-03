<?php

namespace LaravelErp\Exceptions;

use Exception;

class DoesNotExistError extends Exception
{
    // custom exception message
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}