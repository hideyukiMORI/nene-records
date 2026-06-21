<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class ListBlocksFieldsOutput
{
    /** @param list<ListBlocksFieldItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
