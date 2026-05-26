<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

final readonly class ListMediaOutput
{
    /** @param list<ListMediaItem> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
