<?php

namespace Ottosmops\Settings\Exceptions;

use Exception;

class NoKeyIsFound extends Exception
{
    protected $message = 'The requested setting key was not found.';

    public function __construct(string $key = null, int $code = 0, Exception $previous = null)
    {
        if ($key) {
            $this->message = "Setting key '{$key}' was not found.";
        }

        parent::__construct($this->message, $code, $previous);
    }
}
