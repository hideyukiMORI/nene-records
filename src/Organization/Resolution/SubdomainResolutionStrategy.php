<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves org slug from subdomain: org1.nene-records.com → "org1".
 *
 * Configure BASE_DOMAIN=nene-records.com in .env.
 * Requests to the bare base domain (no subdomain) return null.
 */
final readonly class SubdomainResolutionStrategy implements OrgResolutionStrategyInterface, ApexAwareStrategyInterface
{
    public function __construct(
        private string $baseDomain,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        $host = $this->host($request);

        $baseParts = explode('.', $this->baseDomain);
        $hostParts = explode('.', $host);

        // Host must have more segments than baseDomain to have a subdomain
        if (count($hostParts) <= count($baseParts)) {
            return null;
        }

        // Verify the tail matches baseDomain
        $tail = array_slice($hostParts, -count($baseParts));
        if ($tail !== $baseParts) {
            return null;
        }

        return $hostParts[0]; // e.g. "org1"
    }

    public function isApex(ServerRequestInterface $request): bool
    {
        return $this->host($request) === $this->baseDomain;
    }

    private function host(ServerRequestInterface $request): string
    {
        $host = $request->getUri()->getHost();

        return str_contains($host, ':') ? explode(':', $host)[0] : $host;
    }
}
