<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;

final readonly class UpdateEntityInput
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public ?string $slug = null,
        public EntityStatus $status = EntityStatus::Draft,
        public ?DateTimeImmutable $publishedAt = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?DateTimeImmutable $scheduledAt = null,
        public ?string $layout = null,
    ) {
    }
}
