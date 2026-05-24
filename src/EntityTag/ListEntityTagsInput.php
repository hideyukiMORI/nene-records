<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

final readonly class ListEntityTagsInput
{
    public function __construct(
        public int $entityId,
    ) {
    }
}
