<?php

namespace App\Modules\Auth\Exceptions;

use Exception;

class UnableToLoginException extends Exception
{
    const int USER_CANNOT_BE_FOUND = 1;

    const int INVALID_CREDENTIALS = 2;

    public static function becauseUserCannotBeFound(): self
    {
        return new self(
            message: 'Unable to log in with the provided credentials.',
            code: self::USER_CANNOT_BE_FOUND,
        );
    }

    public static function becauseOfInvalidCredentials(): self
    {
        return new self(
            message: 'Unable to log in with the provided credentials.',
            code: self::INVALID_CREDENTIALS,
        );
    }
}
