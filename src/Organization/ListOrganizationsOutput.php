<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

final readonly class ListOrganizationsOutput
{
    /** @param list<ListOrganizationItem> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
