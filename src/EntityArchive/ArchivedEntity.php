<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

final readonly class ArchivedEntity
{
    /**
     * @param array<string, mixed> $snapshot
     */
    public function __construct(
        public int $originalEntityId,
        public int $entityTypeId,
        public string $entityTypeSlug,
        public string $entityTypeName,
        public ?string $entitySlug,
        public string $entityStatus,
        public ?\DateTimeImmutable $deletedAt,
        public \DateTimeImmutable $archivedAt,
        public string $archivedReason,
        public array $snapshot,
    ) {
    }
}
