<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ReorderEntitiesInput
{
    /** @param list<int> $ids record ids in the desired order; menu_order = position */
    public function __construct(
        public array $ids,
    ) {
    }
}
