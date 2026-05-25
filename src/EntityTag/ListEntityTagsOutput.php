<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

final readonly class ListEntityTagsOutput
{
    /** @param list<ListEntityTagItem> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
