<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Subdomain SaaS resolution that also accepts bring-your-own custom domains.
 *
 * In subdomain mode the active host is normally `slug.<base-domain>` → org slug.
 * But a tenant on a paid plan may point their own domain (`blog.example.com`) at
 * our edge. `SubdomainResolutionStrategy` returns null for such hosts (the tail
 * doesn't match the base domain), which would 404 before the middleware's custom
 * domain lookup runs. This composite fills that gap:
 *
 *  1. base-domain subdomain → org slug (delegated to {@see SubdomainResolutionStrategy}).
 *  2. the apex itself → null (the apex landing handles it, via ApexAware).
 *  3. anything else → the full host, so {@see \NeNeRecords\Organization\Resolution\OrgResolverMiddleware}
 *     resolves it through `OrganizationRepository::findByCustomDomain()`.
 *
 * `TlsCheckHandler` already authorizes certificate issuance for registered custom
 * domains, so this is the remaining wiring needed for end-to-end custom domains.
 */
final readonly class SubdomainOrCustomDomainResolutionStrategy implements OrgResolutionStrategyInterface, ApexAwareStrategyInterface
{
    public function __construct(
        private SubdomainResolutionStrategy $subdomain,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        // Prefer a base-domain subdomain (→ org slug).
        $slug = $this->subdomain->resolve($request);
        if ($slug !== null) {
            return $slug;
        }

        // The bare base domain carries no tenant — let the apex landing handle it.
        if ($this->subdomain->isApex($request)) {
            return null;
        }

        // Otherwise it's a bring-your-own custom domain: hand the full host to the
        // middleware, which looks it up via findByCustomDomain().
        $host = $request->getUri()->getHost();
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        return $host !== '' ? $host : null;
    }

    public function isApex(ServerRequestInterface $request): bool
    {
        return $this->subdomain->isApex($request);
    }
}
