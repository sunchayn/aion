<?php

namespace App\Modules\Auth\Exceptions;

use RuntimeException;

final class InvalidRefreshTokenException extends RuntimeException
{
    public static function becauseTokenIsInvalid(): self
    {
        return new self('The provided refresh token is invalid or has expired.');
    }
}
