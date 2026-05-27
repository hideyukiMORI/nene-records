<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves org slug from the ORG_SLUG environment variable.
 *
 * Intended for:
 *  - Local development (set ORG_SLUG=myorg in .env)
 *  - Single-server deployments where one org owns the entire instance
 *
 * Returns null when ORG_SLUG is not set.
 */
final readonly class EnvResolutionStrategy implements OrgResolutionStrategyInterface
{
    public function __construct(
        private ?string $orgSlug,
    ) {
    }

    public function resolve(ServerRequestInterface $request): ?string
    {
        return $this->orgSlug !== '' ? $this->orgSlug : null;
    }
}
