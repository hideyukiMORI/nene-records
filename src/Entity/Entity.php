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
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public ?DateTimeImmutable $deletedAt = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?DateTimeImmutable $scheduledAt = null,
    ) {
    }
}
