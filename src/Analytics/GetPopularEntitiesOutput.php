<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class GetPopularEntitiesOutput
{
    /**
     * @param list<PopularEntityItem> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
