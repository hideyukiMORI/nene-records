<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

interface DefaultContentTypeSeederInterface
{
    /**
     * Slugs of the seeded default entity types. Consumers that reconcile
     * seed residue (org import, #952) key off this list — an entity type is
     * only ever treated as "seed leftover" when its slug is in here.
     *
     * @var list<string>
     */
    public const SEED_SLUGS = ['posts', 'pages'];

    /**
     * Seed the default entity types (Posts, Pages) and their field definitions
     * for the given organization.
     *
     * Implementations MUST be idempotent — calling this multiple times for the
     * same organization must not create duplicate rows.
     */
    public function seed(int $organizationId): void;
}
