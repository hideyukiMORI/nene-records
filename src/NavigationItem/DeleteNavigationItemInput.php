<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

final readonly class DeleteNavigationItemInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
