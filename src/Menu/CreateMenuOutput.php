<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class CreateMenuOutput
{
    public function __construct(
        public Menu $menu,
    ) {
    }
}
