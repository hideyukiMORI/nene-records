<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class ReorderEntityTypesInput
{
    /**
     * @param list<int> $ids Entity type ids in the desired display order.
     */
    public function __construct(
        public array $ids,
    ) {
    }
}
