<?php

namespace App\Http\Web\Auth\Requests;

use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OAuthRedirectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation_type' => ['required', Rule::enum(OperationTypeEnum::class)],
        ];
    }
}
