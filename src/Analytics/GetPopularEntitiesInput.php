<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class GetPopularEntitiesInput
{
    public function __construct(
        public int $days,
        public int $limit,
    ) {
    }
}
