<?php

namespace Ottosmops\Settings\Exceptions;

class NoKeyIsFound extends \Exception
{

    public function report($key)
    {
        return "key $key is not found in the settings";
    }
}
