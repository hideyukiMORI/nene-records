<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class ListFieldDefsOutput
{
    /**
     * @param list<ListFieldDefItem> $items
     */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
