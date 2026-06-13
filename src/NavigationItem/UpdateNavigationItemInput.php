<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class UpdateNavigationItemInput
{
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
        public string $location,
        public int $displayOrder,
    ) {
    }
}
