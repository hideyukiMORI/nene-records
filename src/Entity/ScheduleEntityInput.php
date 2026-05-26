<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;

final readonly class ScheduleEntityInput
{
    public function __construct(
        public int $id,
        public DateTimeImmutable $scheduledAt,
    ) {
    }
}
