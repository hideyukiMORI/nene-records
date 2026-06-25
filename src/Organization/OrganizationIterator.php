<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\RequestScopedHolder;

/**
 * Runs org-scoped work once per active organization, scoping the shared org-id
 * holder to each in turn so org-scoped repositories operate on that tenant.
 *
 * For org-agnostic batch jobs that must cover every tenant — notably the
 * scheduled-publish cron, which in subdomain (multi-tenant) mode has no single
 * org context (the cron hits the app with an internal host).
 */
final readonly class OrganizationIterator
{
    /**
     * @param RequestScopedHolder<int> $orgHolder
     */
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private RequestScopedHolder $orgHolder,
    ) {
    }

    /**
     * @param callable(int $organizationId): void $work
     */
    public function forEachActive(callable $work): void
    {
        foreach ($this->organizations->findAllActive() as $org) {
            if ($org->id === null) {
                continue;
            }

            $this->orgHolder->set($org->id);
            $work($org->id);
        }
    }
}
