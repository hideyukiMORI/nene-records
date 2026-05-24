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
    ) {
    }
}
