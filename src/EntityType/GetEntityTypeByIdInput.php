<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class GetEntityTypeByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
