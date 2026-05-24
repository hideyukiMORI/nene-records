<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

final readonly class ListEntityRelationsOutput
{
    /** @param list<EntityRelationListItem> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
