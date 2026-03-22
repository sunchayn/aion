<?php

namespace App\Http\Api\Auth\Requests;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UnlinkSocialAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(ProviderEnum::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'provider' => $this->route('provider'),
        ]);
    }
}
