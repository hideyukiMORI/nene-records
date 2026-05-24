<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class AccessStatsDayItem
{
    public function __construct(
        public string $date,
        public int $requestCount,
        public float $avgDurationMs,
    ) {
    }
}
