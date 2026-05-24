<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class ListEntityTypesOutput
{
    /** @param list<ListEntityTypeItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
