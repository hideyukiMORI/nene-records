<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class NavigationItem
{
    public function __construct(
        public ?int $id,
        public string $label,
        public string $url,
        public string $location,
        public int $displayOrder,
        public string $createdAt,
        public string $updatedAt,
        // Named menu this item belongs to (backfilled from location; the source
        // of truth once the UI migrates off `location`).
        public ?int $menuId = null,
    ) {
    }
}
