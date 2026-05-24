<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class DeleteEntityInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
