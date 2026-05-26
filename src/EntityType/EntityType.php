<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class EntityType
{
    /**
     * @param array<string, string>|null $labels Locale-keyed display names, e.g. {"ja":"投稿","fr":"Articles"}.
     *                                            Null means no overrides; `$name` is used as fallback.
     */
    public function __construct(
        public string $name,
        public string $slug,
        public bool $isPinned = false,
        public ?int $id = null,
        public ?array $labels = null,
    ) {
    }
}
