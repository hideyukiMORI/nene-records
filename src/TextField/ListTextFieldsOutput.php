<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

final readonly class ListTextFieldsOutput
{
    /** @param list<ListTextFieldItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
