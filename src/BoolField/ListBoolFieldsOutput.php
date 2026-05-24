<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class ListBoolFieldsOutput
{
    /** @param list<ListBoolFieldItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
