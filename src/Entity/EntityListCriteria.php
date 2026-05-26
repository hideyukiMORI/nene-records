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
    ) {
    }
}
