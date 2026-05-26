<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class ListNavigationItemsOutput
{
    /** @param list<NavigationItem> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
