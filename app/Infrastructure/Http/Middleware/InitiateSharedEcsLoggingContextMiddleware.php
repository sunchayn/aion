<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Logging\ECS\Fields\HttpRequestsFieldsAggregator;
use App\Infrastructure\Logging\ECS\Fields\UserField;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class InitiateSharedEcsLoggingContextMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
        private AuthManager $authManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->logger->shareContext([
            new HttpRequestsFieldsAggregator($request),
            new UserField(user: $this->authManager->user()),
        ]);

        return $next($request);
    }
}
