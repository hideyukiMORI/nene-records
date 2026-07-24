<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Http\ClockInterface;
use Nene2\Middleware\RateLimitStorageInterface;
use NeNeRecords\Http\ClientIp;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Public LP-beacon ingest (Path B / #1007, #1008). Static LP pages (served by the web
 * server, invisible to the access-log middleware) POST a tiny payload here so their visits
 * ride the same pipeline as SSR — one hash recipe, one store, unified aggregation. The hub-
 * owned snippet sends `{path, referrer, query}` via `navigator.sendBeacon`.
 *
 * Abuse controls for this unauthenticated endpoint: a per-IP rate limit, a strict path
 * allowlist (unknown paths are silently dropped), and a bounded body. Visitor fields are
 * computed only when the org opts in (via {@see VisitorFieldsResolver}); raw IP/UA/referer/
 * query are never stored. Always answers 204 (fire-and-forget) except 429 when throttled.
 */
final readonly class BeaconIngestHandler
{
    private const MAX_BODY_BYTES = 2048;

    /**
     * @param list<string> $allowedPaths exact public LP paths this endpoint will record
     */
    public function __construct(
        private AccessLogRepositoryInterface $accessLogs,
        private VisitorFieldsResolver $visitor,
        private ClockInterface $clock,
        private RateLimitStorageInterface $rateLimit,
        private ResponseFactoryInterface $responses,
        private array $allowedPaths = ['/invoice-lp', '/clear-lp', '/deal-lp', '/contact', '/inquiry'],
        private int $maxPerWindow = 120,
        private int $windowSeconds = 60,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $ip = ClientIp::resolve($request);

        $hit = $this->rateLimit->hit('beacon:' . $ip, $this->windowSeconds);
        if ($hit['count'] > $this->maxPerWindow) {
            return $this->responses->createResponse(429);
        }

        $payload = $this->decode($request);
        $path = $this->allowedPath($payload);

        // Unknown/absent path → accept and drop (204). Never records arbitrary paths.
        if ($path !== null) {
            $this->record($request, $ip, $path, $payload);
        }

        return $this->responses->createResponse(204);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function record(ServerRequestInterface $request, string $ip, string $path, array $payload): void
    {
        $referrer = is_string($payload['referrer'] ?? null) ? $payload['referrer'] : '';
        $query = is_string($payload['query'] ?? null) ? $payload['query'] : '';

        $visitor = $this->visitor->resolve($ip, $referrer, $query, $request->getHeaderLine('User-Agent'));

        try {
            $this->accessLogs->insert(new AccessLogEntry(
                requestId: null,
                method: 'BEACON',
                path: $path,
                statusCode: 204,
                durationMs: 0.0,
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
        } catch (Throwable) {
            // Fire-and-forget: a failed insert must never surface to the beacon caller.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '' || strlen($raw) > self::MAX_BODY_BYTES) {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function allowedPath(array $payload): ?string
    {
        $path = $payload['path'] ?? null;
        if (!is_string($path)) {
            return null;
        }

        // Normalise a trailing slash so "/clear-lp/" and "/clear-lp" match one allowlist entry.
        $normalised = rtrim($path, '/');
        if ($normalised === '') {
            $normalised = '/';
        }

        return in_array($normalised, $this->allowedPaths, true) ? $normalised : null;
    }
}
