<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class EntityListCriteria
{
    /** @param list<string> $tagSlugs */
    public function __construct(
        public ?int $entityTypeId = null,
        public array $tagSlugs = [],
    ) {
    }
}
