<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class UpdateEntityTypeInput
{
    /**
     * @param array<string, string>|null $labels
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $isPinned = false,
        public ?array $labels = null,
        public ?string $permalinkPattern = null,
        public ?string $previousPermalinkPattern = null,
    ) {
    }
}
