<?php

namespace App\Http\Api\Auth\Requests;

use App\Modules\Auth\OAuthProviders\Enum\OAuthTokenTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Auth\OAuthProviders\ValueObjects\OAuthToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ContinueWithOAuthRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(ProviderEnum::class)],
            'token' => ['required', 'string'],
            'type' => ['nullable', 'string', Rule::enum(OAuthTokenTypeEnum::class)],
            'operation_type' => ['nullable', 'string', Rule::enum(OperationTypeEnum::class)],
        ];
    }

    public function getOAuthToken(): OAuthToken
    {
        $tokenType = $this->enum('type', OAuthTokenTypeEnum::class);

        return $tokenType === OAuthTokenTypeEnum::AuthorizationToken
            ? OAuthToken::authorizationToken($this->validated('token'))
            : OAuthToken::accessToken($this->validated('token'));
    }
}
