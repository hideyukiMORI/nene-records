<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class DeleteMenuInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
