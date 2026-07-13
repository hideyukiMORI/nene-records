<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class EntityListCriteria
{
    /**
     * @param list<string> $tagSlugs
     * @param array<string, int> $relationFilters
     */
    public function __construct(
        public ?int $entityTypeId = null,
        public array $tagSlugs = [],
        public array $relationFilters = [],
        public ?EntityStatus $status = null,
        public ?string $q = null,
        public EntitySortKey $sortKey = EntitySortKey::Id,
        public EntitySortOrder $sortOrder = EntitySortOrder::Desc,
        // Publish-date window (compared against the ISO `published_at`). `from` is
        // inclusive; `toExclusive` is the exclusive upper bound (e.g. the day after
        // the last wanted day) so ISO timestamps within the range still match.
        public ?string $publishedFrom = null,
        public ?string $publishedToExclusive = null,
        /** When true, restrict to records carrying a non-empty custom permalink (#682). */
        public bool $hasPermalink = false,
        /**
         * When true, force published-only regardless of the `status` filter. Set for
         * unauthenticated (anonymous) callers so the open content-read surface can
         * never surface drafts/scheduled records via `?status`. See #828.
         */
        public bool $publishedOnly = false,
    ) {
    }
}
