<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class EntityRevision
{
    public function __construct(
        public int $entityId,
        public EntityRevisionAction $action,
        public string $status,
        public ?string $previousStatus,
        public ?string $slug,
        public ?string $previousSlug,
        public ?int $actorUserId,
        public string $createdAt,
        public ?int $id = null,
    ) {
    }
}
