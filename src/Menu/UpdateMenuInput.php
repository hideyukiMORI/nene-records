<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class UpdateMenuInput
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $location,
    ) {
    }
}
