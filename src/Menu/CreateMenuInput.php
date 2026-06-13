<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class CreateMenuInput
{
    public function __construct(
        public string $name,
        public ?string $location,
    ) {
    }
}
