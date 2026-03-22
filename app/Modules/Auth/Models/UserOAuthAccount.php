<?php

namespace App\Modules\Auth\Models;

use App\Modules\Auth\OAuthProviders\Enum\ProviderEnum;
use App\Modules\Shared\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Modules\Auth\Models\UserOAuthAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User|null $user
 * @property int $id
 * @property int $user_id
 * @property ProviderEnum $provider
 * @property string $provider_user_id
 * @property string|null $provider_avatar
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static UserOAuthAccountFactory factory($count = null, $state = [])
 * @method static Builder<static>|UserOAuthAccount newModelQuery()
 * @method static Builder<static>|UserOAuthAccount newQuery()
 * @method static Builder<static>|UserOAuthAccount query()
 *
 * @mixin Model
 */
class UserOAuthAccount extends Model
{
    /** @use HasFactory<UserOAuthAccountFactory> */
    use HasFactory;

    protected $table = 'user_oauth_accounts';

    protected static function newFactory(): UserOAuthAccountFactory
    {
        return UserOAuthAccountFactory::new();
    }

    protected function casts(): array
    {
        return [
            'provider' => ProviderEnum::class,
            'expires_at' => 'immutable_datetime',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
