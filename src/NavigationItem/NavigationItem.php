<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class NavigationItem
{
    public function __construct(
        public ?int $id,
        public string $label,
        public string $url,
        public int $displayOrder,
        public string $createdAt,
        public string $updatedAt,
        // Named menu this item belongs to.
        public ?int $menuId = null,
    ) {
    }
}
