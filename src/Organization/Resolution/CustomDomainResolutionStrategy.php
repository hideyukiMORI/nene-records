<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves org by full custom domain: org1.com → looks up organizations.custom_domain.
 *
 * Use when tenants bring their own domain (CNAME → your server).
 * The returned value is the raw Host header — OrgResolverMiddleware will look it up
 * via OrganizationRepository::findByCustomDomain().
 */
final readonly class CustomDomainResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function resolve(ServerRequestInterface $request): ?string
    {
        $host = $request->getUri()->getHost();

        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        return $host !== '' ? $host : null;
    }
}
