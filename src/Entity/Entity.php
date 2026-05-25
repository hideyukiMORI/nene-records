<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;

final readonly class Entity
{
    public function __construct(
        public ?int $id,
        public int $entityTypeId,
        public ?string $slug = null,
        public EntityStatus $status = EntityStatus::Draft,
        public ?DateTimeImmutable $publishedAt = null,
        public bool $isDeleted = false,
        public ?DateTimeImmutable $deletedAt = null,
    ) {
    }
}
