<?php

namespace App\Modules\Auth\Models\Concerns;

/**
 * Implements the JWT package requirements for a User model.
 * Drop this trait when switching to a non-JWT auth driver.
 */
trait JwtAuthenticatable
{
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): string
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
