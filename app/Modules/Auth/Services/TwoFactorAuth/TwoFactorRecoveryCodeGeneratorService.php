<?php

namespace App\Modules\Auth\Services\TwoFactorAuth;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TwoFactorRecoveryCodeGeneratorService
{
    /**
     * @return array<int, non-empty-string>
     */
    public function generate(): array
    {
        return Collection::range(1, 8)
            ->map(static fn (): string => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->values()
            ->all();
    }
}
