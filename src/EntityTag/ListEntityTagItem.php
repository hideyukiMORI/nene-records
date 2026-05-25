<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

final readonly class ListEntityTagItem
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
    ) {
    }
}
