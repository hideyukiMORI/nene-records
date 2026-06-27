<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ReorderEntitiesOutput
{
    public function __construct(
        public int $reordered,
    ) {
    }
}
