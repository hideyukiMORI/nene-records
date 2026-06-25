<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Marker for strategies that have an "apex" host carrying no tenant — the bare
 * base domain of a subdomain SaaS (`nene-records.com`), which serves the global
 * landing / signup surface rather than any organization's site.
 *
 * When the strategy resolves no tenant AND the request is for the apex, the
 * middleware passes through as no-tenant (org 0) instead of 404, so the global
 * surface renders. Non-apex unresolved hosts still 404.
 */
interface ApexAwareStrategyInterface
{
    /** True when the request targets the tenant-less base domain (apex). */
    public function isApex(ServerRequestInterface $request): bool;
}
