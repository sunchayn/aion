<?php

namespace App\Modules\Auth\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidEmailVerificationLinkException extends Exception
{
    public static function becauseOfInvalidHash(): self
    {
        return new self('Invalid verification link.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 403);
    }
}
