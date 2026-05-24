<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class DeleteEntityTypeInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
