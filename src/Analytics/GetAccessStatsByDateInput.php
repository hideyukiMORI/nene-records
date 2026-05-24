<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;

final readonly class GetAccessStatsByDateInput
{
    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {
    }
}
