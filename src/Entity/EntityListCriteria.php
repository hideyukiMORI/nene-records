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
    ) {
    }
}
