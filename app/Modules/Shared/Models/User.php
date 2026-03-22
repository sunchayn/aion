<?php

namespace App\Modules\Shared\Models;

use App\Modules\Auth\Models\Concerns\JwtAuthenticatable;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use Carbon\CarbonImmutable;
use Database\Factories\Modules\Users\Models\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property UserTwoFactorAuth|null $twoFactorAuth
 * @property-read Collection<int, UserOAuthAccount> $oauthAccounts
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 *
 * @method HasOne<UserTwoFactorAuth, $this> twoFactorAuth()
 * @method HasMany<UserOAuthAccount, $this> oauthAccounts()
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 *
 * @mixin Model
 */
class User extends Authenticatable implements \Illuminate\Contracts\Auth\Authenticatable, CanResetPassword, JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, JwtAuthenticatable, MustVerifyEmailTrait, Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = now();

        return $this->save();
    }
}
