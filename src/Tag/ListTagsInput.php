<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class ListTagsInput
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
