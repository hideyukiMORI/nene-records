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
        public string $status = EntityStatus::DRAFT,
        public ?DateTimeImmutable $publishedAt = null,
    ) {
    }
}
