<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves org slug from URL path prefix: /org1/api/... → "org1".
 *
 * The middleware strips the prefix from the request URI before routing,
 * so downstream handlers see /api/... rather than /org1/api/...
 *
 * Best for shared-host deployments where wildcard subdomains are not available.
 */
final readonly class PathPrefixResolutionStrategy implements OrgResolutionStrategyInterface
{
    /** @var list<string> Paths that skip org resolution (superadmin, health, auth). */
    private const BYPASS_PREFIXES = [
        '/health',
        '/api/v1/organizations',
        '/api/v1/auth/',
    ];

    public function resolve(ServerRequestInterface $request): ?string
    {
        $path = $request->getUri()->getPath();

        foreach (self::BYPASS_PREFIXES as $bypass) {
            if (str_starts_with($path, $bypass)) {
                return null;
            }
        }

        $trimmed = ltrim($path, '/');
        $parts = explode('/', $trimmed, 2);
        $candidate = $parts[0];

        return $candidate !== '' ? $candidate : null;
    }
}
