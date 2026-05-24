<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class CreateEntityInput
{
    public function __construct(
        public int $entityTypeId,
        public string $status = EntityStatus::DRAFT,
    ) {
    }
}
