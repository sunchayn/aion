<?php

namespace App\Providers;

use App\Modules\Auth\Services\TokenIssuers\AuthTokenIssuerContract;
use App\Modules\Auth\Services\TokenIssuers\NullTokenIssuer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /** @var class-string<AuthTokenIssuerContract> $issuer */
        $issuer = config('auth.tokens.issuer') ?? NullTokenIssuer::class;

        $this->app->bind(AuthTokenIssuerContract::class, $issuer);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootEmailVerificationDefaults();
    }

    private function bootEmailVerificationDefaults(): void
    {
        VerifyEmail::createUrlUsing(function (MustVerifyEmail&Model $notifiable): string {
            $apiVerificationUrl = URL::temporarySignedRoute(
                'api.v1.auth.verification.verify',
                Date::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );

            $queryParamsToForward = parse_url($apiVerificationUrl, PHP_URL_QUERY);

            // We pass the id and hash explicitly as the frontend needs them to call the API later,
            // along with the expires and signature query params.
            $queryParamsToForward .= '&id='.$notifiable->getKey().'&hash='.sha1($notifiable->getEmailForVerification());

            return config('webhooks.frontend.redirects.email_verification_notice')."?{$queryParamsToForward}";
        });
    }
}
