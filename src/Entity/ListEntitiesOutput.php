<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ListEntitiesOutput
{
    /** @param list<ListEntityItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
