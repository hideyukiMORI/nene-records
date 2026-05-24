<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

final readonly class AttachEntityTagInput
{
    public function __construct(
        public int $entityId,
        public int $tagId,
    ) {
    }
}
