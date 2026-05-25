<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

final readonly class ListEntityRelationsOutput
{
    /** @param list<ListEntityRelationItem> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
