<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Marker for resolution strategies that consume a leading URI path segment as
 * the tenant identifier (directory / path mode: `/org1/posts/1`).
 *
 * The middleware strips this prefix before routing so downstream handlers see
 * `/posts/1`, and re-exposes it on the `nene2.base_prefix` request attribute so
 * public URL generation (canonical / sitemap / `<base href>`) can re-add it —
 * keeping every emitted URL under the tenant's sub-directory.
 *
 * Strategies that resolve the tenant from elsewhere (subdomain / env / custom
 * domain) do NOT implement this: their base prefix is empty.
 */
interface UriPrefixStrippingStrategyInterface
{
    /** The URI path prefix this strategy consumes (e.g. `/org1`), `''` if none. */
    public function basePrefix(ServerRequestInterface $request): string;
}
