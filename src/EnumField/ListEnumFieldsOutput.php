<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class ListEnumFieldsOutput
{
    /** @param list<ListEnumFieldItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
