<?php

namespace App\Modules\Auth\Models;

use App\Modules\Shared\Models\User;
use Database\Factories\Modules\Auth\Models\UserTwoFactorAuthFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $secret
 * @property array<int, string>|null $recovery_codes
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static UserTwoFactorAuthFactory factory($count = null, $state = [])
 * @method static Builder<static>|UserTwoFactorAuth newModelQuery()
 * @method static Builder<static>|UserTwoFactorAuth newQuery()
 * @method static Builder<static>|UserTwoFactorAuth query()
 *
 * @mixin Model
 */
class UserTwoFactorAuth extends Model
{
    /** @use HasFactory<UserTwoFactorAuthFactory> */
    use HasFactory;

    protected $table = 'user_two_factor_auth';

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'confirmed_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): UserTwoFactorAuthFactory
    {
        return UserTwoFactorAuthFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
