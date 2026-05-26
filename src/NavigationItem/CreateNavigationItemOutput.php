<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class CreateNavigationItemOutput
{
    public function __construct(
        public NavigationItem $item,
    ) {
    }
}
