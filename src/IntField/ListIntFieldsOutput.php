<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class ListIntFieldsOutput
{
    /** @param list<ListIntFieldItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
