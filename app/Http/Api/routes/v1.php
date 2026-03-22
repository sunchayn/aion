<?php

use App\Http\Api\Auth\Controllers as AuthControllers;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->group(function (): void {
        Route::prefix('tokens')->group(function (): void {
            Route::post('/', AuthControllers\LoginWithCredentialsController::class)
                ->name('auth.tokens.store');

            Route::post('refresh', AuthControllers\RefreshTokenController::class)
                ->name('auth.tokens.refresh');
        });

        Route::post('oauth-tokens', AuthControllers\ContinueWithOAuthController::class)
            ->name('auth.oauth-tokens.store');

        Route::post('users', AuthControllers\RegisterWithCredentialsController::class)
            ->name('auth.users.store');

        Route::prefix('passwords')->group(function (): void {
            Route::post('reset-requests', AuthControllers\ForgotPasswordController::class)
                ->name('auth.passwords.requests.store');

            Route::patch('reset-requests', AuthControllers\ResetPasswordController::class)
                ->name('auth.passwords.resets.patch');
        });

        Route::post('two-factor/challenges', AuthControllers\TwoFactorChallengeController::class)
            ->name('auth.two-factor.challenges.store');

        Route::prefix('email')->group(function (): void {
            Route::post('verify/{id}/{hash}', AuthControllers\VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('auth.verification.verify');
        });

        Route::prefix('magic-links')->group(function (): void {
            Route::post('/', AuthControllers\SendMagicLinkController::class)
                ->name('auth.magic-links.store');

            Route::post('verifications', AuthControllers\VerifyMagicLinkController::class)
                ->name('auth.magic-links.verifications.store');
        });
    });

/*
 * Protected Routes.
 */

Route::middleware('auth')
    ->group(function (): void {
        Route::prefix('auth')
            ->group(function (): void {
                Route::post('oauth/link', AuthControllers\LinkSocialAccountController::class)
                    ->name('auth.oauth-links.store');

                Route::post('logout', AuthControllers\LogoutController::class)->name('auth.logout');

                Route::prefix('two-factor')->group(function (): void {
                    Route::post('/', AuthControllers\EnableTwoFactorController::class)
                        ->name('auth.two-factor.store');

                    Route::delete('/', AuthControllers\DisableTwoFactorController::class)
                        ->name('auth.two-factor.destroy');

                    Route::post('confirmations', AuthControllers\ConfirmTwoFactorController::class)
                        ->name('auth.two-factor.confirmations.store');

                    Route::post('recovery-codes', AuthControllers\RegenerateTwoFactorRecoveryCodesController::class)
                        ->name('auth.two-factor.recovery-codes.store');
                });

                Route::delete('oauth/link/{provider}', AuthControllers\UnlinkSocialAccountController::class)
                    ->name('auth.oauth-links.destroy');

                Route::prefix('email')->group(function (): void {
                    Route::post('verification-notification', AuthControllers\ResendEmailVerificationController::class)
                        ->middleware(['throttle:6,1'])
                        ->name('auth.verification.send');
                });
            });
    });
