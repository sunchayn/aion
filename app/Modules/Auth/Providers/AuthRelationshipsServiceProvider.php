<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Auth\Models\UserOAuthAccount;
use App\Modules\Auth\Models\UserTwoFactorAuth;
use App\Modules\Shared\Models\User;
use Illuminate\Support\ServiceProvider;

class AuthRelationshipsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::resolveRelationUsing('oauthAccounts', fn (User $user) => $user->hasMany(UserOAuthAccount::class));

        User::resolveRelationUsing('twoFactorAuth', fn (User $user) => $user->hasOne(UserTwoFactorAuth::class));

        User::resolveRelationUsing('magicLinks', fn (User $user) => $user->hasMany(MagicLink::class));
    }
}
