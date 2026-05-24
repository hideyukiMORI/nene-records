<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class EntityType
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $id = null,
    ) {
    }
}
