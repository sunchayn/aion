<?php

use App\Http\Web\Auth\Controllers\OAuthRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('oauth/{provider}/redirect', OAuthRedirectController::class)
    ->name('auth.oauth-redirect');
