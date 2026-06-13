<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class Menu
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $slug,
        /** header | footer | null (null = standalone, surfaced via a menu widget) */
        public ?string $location,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
