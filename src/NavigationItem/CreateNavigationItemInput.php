<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class CreateNavigationItemInput
{
    public function __construct(
        public string $label,
        public string $url,
        public int $displayOrder,
        public ?int $menuId = null,
    ) {
    }
}
