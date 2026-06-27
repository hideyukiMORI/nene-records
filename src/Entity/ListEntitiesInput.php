<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ListEntitiesInput
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
        public EntityListCriteria $criteria = new EntityListCriteria(),
        /** When true, populate each item's viewCount (opt-in, #674). */
        public bool $includeViews = false,
    ) {
    }
}
