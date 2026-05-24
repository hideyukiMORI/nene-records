<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class ListTagItem
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
    ) {
    }
}
