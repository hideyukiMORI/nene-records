<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class UpdateEntityInput
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
    ) {
    }
}
