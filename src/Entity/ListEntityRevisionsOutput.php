<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ListEntityRevisionsOutput
{
    /**
     * @param list<EntityRevision> $items
     */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
