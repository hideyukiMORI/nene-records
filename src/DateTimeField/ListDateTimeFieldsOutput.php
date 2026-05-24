<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class ListDateTimeFieldsOutput
{
    /** @param list<ListDateTimeFieldItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
