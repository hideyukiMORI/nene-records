<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Http\ClockInterface;
use Nene2\Middleware\RequestIdMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class AccessLogMiddleware implements MiddlewareInterface
{
    /** @param list<string> $excludedPaths */
    public function __construct(
        private AccessLogRepositoryInterface $accessLogs,
        private LoggerInterface $logger,
        private ClockInterface $clock,
        private array $excludedPaths = ['/health', '/', '/machine/health', '/examples/ping'],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath() ?: '/';

        if (in_array($path, $this->excludedPaths, true)) {
            return $handler->handle($request);
        }

        $startedAt = hrtime(true);
        $response = $handler->handle($request);

        try {
            $this->accessLogs->insert(new AccessLogEntry(
                requestId: $this->requestId($request),
                method: $request->getMethod(),
                path: $path,
                statusCode: $response->getStatusCode(),
                durationMs: $this->durationMilliseconds($startedAt),
                accessedAt: $this->clock->now(),
            ));
        } catch (Throwable $exception) {
            $this->logger->error('Failed to persist access log entry.', [
                'exception' => $exception->getMessage(),
                'path' => $path,
                'method' => $request->getMethod(),
            ]);
        }

        return $response;
    }

    private function requestId(ServerRequestInterface $request): ?string
    {
        $requestId = $request->getAttribute(RequestIdMiddleware::ATTRIBUTE);

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }

    private function durationMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 3);
    }
}
