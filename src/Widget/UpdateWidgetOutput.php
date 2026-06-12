<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class UpdateWidgetOutput
{
    public function __construct(
        public Widget $item,
    ) {
    }
}
