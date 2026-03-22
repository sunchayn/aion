<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class InitiateSharedLoggingContextMiddleware
{
    public function __construct(
        private LoggerInterface $logger,
        private AuthManager $authManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (method_exists($this->logger, 'shareContext')) {
            $this->logger->shareContext([
                'user' => [
                    'id' => $this->authManager->id(),
                ],
                'http' => [
                    'request' => [
                        'id' => $this->getRequestId($request),
                        'method' => $request->method(),
                    ],
                ],
                'url' => [
                    'full' => $request->fullUrl(),
                    'path' => $request->path(),
                    'scheme' => $request->getScheme(),
                    'domain' => $request->getHost(),
                    'port' => $request->getPort(),
                    'query' => $request->getQueryString(),
                ],
                'user_agent' => [
                    'original' => $request->userAgent(),
                ],
                'client' => [
                    'ip' => $request->ip(),
                ],
            ]);
        }

        return $next($request);
    }

    private function getRequestId(Request $request): string
    {
        return $request->header(
            // First Check if request ID already exists in headers (e.g., from another service calling this api).
            'x-request-id',
            default: Str::uuid()->toString(),
        );
    }
}
