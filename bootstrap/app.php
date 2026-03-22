<?php

use App\Infrastructure\Http\Middleware\InitiateSharedLoggingContextMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        then: function (): void {
            Route::prefix('/')->group(base_path('app/Http/Web/routes/web.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(function (): void {
                    Route::prefix('v1')->name('api.v1.')->group(base_path('app/Http/Api/routes/v1.php'));
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            HandleCors::class,
            SubstituteBindings::class,
        ]);

        $middleware->append(
            InitiateSharedLoggingContextMiddleware::class,
        );

        $middleware->alias([
            'auth' => Authenticate::class,
            'throttle' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (): true => true);
    })->create();
