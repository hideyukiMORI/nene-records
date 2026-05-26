<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class CreateEntityTypeOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $isPinned,
    ) {
    }
}
