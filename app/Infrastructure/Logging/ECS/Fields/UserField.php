<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Contracts\FieldContract;
use App\Modules\Shared\Models\User;

final readonly class UserField implements FieldContract
{
    public function __construct(
        private ?User $user,
    ) {}

    public function toArray(): array
    {
        if (! $this->user instanceof User) {
            return [
                'user' => [],
            ];
        }

        return [
            'user' => [
                'id' => $this->user->getKey(),
            ],
        ];
    }
}
