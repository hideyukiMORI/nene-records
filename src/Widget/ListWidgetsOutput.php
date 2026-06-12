<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class ListWidgetsOutput
{
    /** @param list<Widget> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
