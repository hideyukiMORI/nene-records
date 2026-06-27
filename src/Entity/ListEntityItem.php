<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ListEntityItem
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public ?string $slug,
        public ?string $permalink,
        public string $status,
        public ?string $publishedAtIso,
        public bool $isDeleted,
        public ?string $deletedAtIso,
        public ?string $scheduledAtIso = null,
        public ?string $createdAtIso = null,
        public ?string $updatedAtIso = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public int $menuOrder = 0,
    ) {
    }
}
