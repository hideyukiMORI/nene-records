<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class UpdateEntityOutput
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public ?string $slug,
        public string $status,
        public ?string $publishedAtIso,
        public bool $isDeleted,
        public ?string $deletedAtIso,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $scheduledAtIso = null,
        public ?string $layout = null,
        public ?string $permalink = null,
        public ?bool $showComments = null,
        public ?bool $showRelated = null,
    ) {
    }
}
