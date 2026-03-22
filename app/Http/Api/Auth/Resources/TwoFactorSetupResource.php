<?php

namespace App\Http\Api\Auth\Resources;

use App\Modules\Auth\DataTransferObjects\TwoFactorSetupPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read TwoFactorSetupPayload $resource
 */
class TwoFactorSetupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'secret' => $this->resource->secret,
            'qr_code_url' => $this->resource->qrCodeUrl,
            'recovery_codes' => $this->resource->recoveryCodes,
        ];
    }
}
