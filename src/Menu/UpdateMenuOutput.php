<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class UpdateMenuOutput
{
    public function __construct(
        public Menu $menu,
    ) {
    }
}
