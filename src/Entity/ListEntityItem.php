<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ListEntityItem
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public ?string $slug,
        public string $status,
        public ?string $publishedAtIso,
        public bool $isDeleted,
        public ?string $deletedAtIso,
        public ?string $scheduledAtIso = null,
    ) {
    }
}
