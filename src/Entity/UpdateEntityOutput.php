<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class UpdateEntityOutput
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public bool $isDeleted,
        public ?string $deletedAtIso,
    ) {
    }
}
