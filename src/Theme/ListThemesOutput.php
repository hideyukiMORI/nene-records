<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

final readonly class ListThemesOutput
{
    /** @param list<Theme> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
