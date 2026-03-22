<?php

declare(strict_types=1);

namespace App\Modules\Domain\DTOs;

use App\Modules\Shared\Models\User;
use Illuminate\Http\Request;

final readonly class UserData
{
    public function __construct(
        public User $user,
        public string $role,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            User::findOrFail($request->validated('user_id')),
            $request->validated('role'),
        );
    }
}
