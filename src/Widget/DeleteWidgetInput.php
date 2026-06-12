<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class DeleteWidgetInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
