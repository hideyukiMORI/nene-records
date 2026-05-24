<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

final readonly class DetachEntityTagInput
{
    public function __construct(
        public int $entityId,
        public int $tagId,
    ) {
    }
}
