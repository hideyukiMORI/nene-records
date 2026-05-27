<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the organization slug from an incoming HTTP request.
 *
 * Implementations cover:
 *  - SubdomainResolutionStrategy  — org1.nene-records.com → "org1"
 *  - CustomDomainResolutionStrategy — org1.com → looks up by custom_domain
 *  - EnvResolutionStrategy        — ORG_SLUG env var (dev / single-server)
 */
interface OrgResolutionStrategyInterface
{
    /**
     * Returns the org slug or custom domain identifier.
     * Returns null when this strategy cannot determine an org from the request.
     */
    public function resolve(ServerRequestInterface $request): ?string;
}
