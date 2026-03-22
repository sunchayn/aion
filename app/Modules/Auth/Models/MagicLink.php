<?php

namespace App\Modules\Auth\Models;

use App\Modules\Shared\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Modules\Auth\Models\MagicLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read CarbonImmutable $expires_at
 * @property-read CarbonImmutable|null $used_at
 * @property-read User|null $user
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static MagicLinkFactory factory($count = null, $state = [])
 * @method static Builder<static>|MagicLink newModelQuery()
 * @method static Builder<static>|MagicLink newQuery()
 * @method static Builder<static>|MagicLink query()
 * @method static Builder<static>|MagicLink valid()
 *
 * @mixin Model
 */
class MagicLink extends Model
{
    /** @use HasFactory<MagicLinkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): MagicLinkFactory
    {
        return MagicLinkFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<MagicLink>  $query
     * @return Builder<MagicLink>
     */
    #[Scope]
    protected function valid(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }
}
