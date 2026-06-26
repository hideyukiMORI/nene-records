<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class CreateEntityInput
{
    public function __construct(
        public int $entityTypeId,
        public ?string $slug = null,
        public EntityStatus $status = EntityStatus::Draft,
        public ?string $layout = null,
        /** Normalized custom permalink, or null to use the type pattern (#651). */
        public ?string $permalink = null,
    ) {
    }
}
