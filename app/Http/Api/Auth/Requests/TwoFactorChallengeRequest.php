<?php

namespace App\Http\Api\Auth\Requests;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

final class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required_without:recovery_code', 'string'],
            'recovery_code' => ['required_without:code', 'string'],
            'two_factor_token' => ['required', 'string'],
        ];
    }

    /**
     * @throws AuthenticationException
     */
    public function getTokenUserId(): int
    {
        try {
            $payload = json_decode(Crypt::decryptString($this->validated('two_factor_token')), true);

            throw_if(! isset($payload['id']) || ! isset($payload['expires_at']) || $payload['expires_at'] < now()->timestamp, AuthenticationException::class);
        } catch (DecryptException) {
            throw new AuthenticationException;
        }

        return $payload['id'];
    }
}
