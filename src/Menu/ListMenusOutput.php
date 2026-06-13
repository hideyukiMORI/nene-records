<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class ListMenusOutput
{
    /** @param list<Menu> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
