<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;

final readonly class Entity
{
    public function __construct(
        public ?int $id,
        public int $entityTypeId,
        public string $status = EntityStatus::DRAFT,
        public ?DateTimeImmutable $publishedAt = null,
        public bool $isDeleted = false,
        public ?DateTimeImmutable $deletedAt = null,
    ) {
    }
}
