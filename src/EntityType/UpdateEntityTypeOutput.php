<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class UpdateEntityTypeOutput
{
    /**
     * @param array<string, string>|null $labels
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $isPinned,
        public ?array $labels = null,
    ) {
    }
}
