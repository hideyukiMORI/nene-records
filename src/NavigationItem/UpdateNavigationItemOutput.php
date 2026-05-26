<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class UpdateNavigationItemOutput
{
    public function __construct(
        public NavigationItem $item,
    ) {
    }
}
