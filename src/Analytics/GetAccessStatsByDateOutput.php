<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class GetAccessStatsByDateOutput
{
    /**
     * @param list<AccessStatsDayItem> $items
     */
    public function __construct(
        public string $from,
        public string $to,
        public array $items,
    ) {
    }
}
