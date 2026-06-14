<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class UpdateNavigationItemInput
{
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
        public int $displayOrder,
        // menu_id is only changed when the client sends it; otherwise preserved.
        public ?int $menuId = null,
        public bool $menuIdProvided = false,
    ) {
    }
}
