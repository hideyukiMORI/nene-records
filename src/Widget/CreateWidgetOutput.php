<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class CreateWidgetOutput
{
    public function __construct(
        public Widget $item,
    ) {
    }
}
