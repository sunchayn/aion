<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\ECS\Fields;

use App\Infrastructure\Logging\ECS\Contracts\FieldContract;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * This field is aggregating different ECS field related to the HTTP request.
 *
 * @see https://www.elastic.co/guide/en/ecs/current/ecs-http.html
 * @see https://www.elastic.co/guide/en/ecs/current/ecs-url.html
 * @see https://www.elastic.co/guide/en/ecs/current/ecs-user_agent.html
 * @see https://www.elastic.co/guide/en/ecs/current/ecs-client.html
 */
final readonly class HttpRequestsFieldsAggregator implements FieldContract
{
    public function __construct(
        private Request $request,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'http' => [
                'request' => [
                    'id' => $this->getRequestId(),
                    'method' => $this->request->method(),
                    'body' => [
                        'bytes' => mb_strlen($this->request->getContent()),
                    ],
                ],
            ],
            'url' => [
                'full' => $this->request->fullUrl(),
                'path' => $this->request->path(),
                'scheme' => $this->request->getScheme(),
                'domain' => $this->request->getHost(),
                'port' => $this->request->getPort(),
                'query' => $this->request->getQueryString(),
            ],
            'user_agent' => [
                'original' => $this->request->userAgent(),
            ],
            'client' => [
                'ip' => $this->request->ip(),
            ],
        ];
    }

    private function getRequestId(): string
    {
        // Check if request ID already exists in headers (e.g., from load balancer)
        $requestId = $this->request->header('x-request-id');

        return $requestId ?? Str::uuid()->toString();
    }
}
