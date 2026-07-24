<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Http\ClockInterface;
use Nene2\Middleware\RequestIdMiddleware;
use NeNeRecords\Http\ClientIp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Persists one access_logs row per request. Base fields (method/path/status/duration) are
 * always recorded except for the infrastructure paths in {@see self::$excludedPaths}.
 *
 * When the owning org opts in (`analytics_visitor_tracking` = true, ADR 0006 / #1007) the
 * privacy-first visitor fields are additionally computed via {@see VisitorFieldsResolver}:
 * a daily-salted org-scoped hash of the client IP (the raw IP is never stored), the referer
 * host only, the utm_* / ref allowlist, and a coarse UA class + bot flag. Opt-in OFF (the
 * default) reproduces the exact pre-Path-B behaviour.
 *
 * Note: the homepage `/` is intentionally NOT excluded (Path B counts front-page visits);
 * only true infrastructure/health endpoints and the beacon ingest (recorded by its own
 * handler as a BEACON row) are skipped.
 */
final readonly class AccessLogMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $excludedPaths
     */
    public function __construct(
        private AccessLogRepositoryInterface $accessLogs,
        private LoggerInterface $logger,
        private ClockInterface $clock,
        private VisitorFieldsResolver $visitor,
        private array $excludedPaths = ['/health', '/machine/health', '/examples/ping', '/api/v1/public/beacon'],
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
            $visitor = $this->visitor->resolve(
                ClientIp::resolve($request),
                $request->getHeaderLine('Referer'),
                $request->getUri()->getQuery(),
                $request->getHeaderLine('User-Agent'),
            );

            $this->accessLogs->insert(new AccessLogEntry(
                requestId: $this->requestId($request),
                method: $request->getMethod(),
                path: $path,
                statusCode: $response->getStatusCode(),
                durationMs: $this->durationMilliseconds($startedAt),
                accessedAt: $this->clock->now(),
                visitorHash: $visitor['visitorHash'],
                refererHost: $visitor['refererHost'],
                utmSource: $visitor['utmSource'],
                utmMedium: $visitor['utmMedium'],
                utmCampaign: $visitor['utmCampaign'],
                ref: $visitor['ref'],
                clientType: $visitor['clientType'],
                isBot: $visitor['isBot'],
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
