<?php

namespace App\Modules\Auth\Responses;

use App\Modules\Shared\Models\User;
use Illuminate\Contracts\Support\Responsable;

interface LoginResponse extends Responsable
{
    /**
     * Get the authenticated user.
     * Useful for non-HTTP contexts like events, tests, or logging where the user is needed.
     */
    public function getUser(): User;
}
