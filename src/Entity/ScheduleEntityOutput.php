<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ScheduleEntityOutput
{
    public function __construct(
        public int $id,
        public string $status,
        public string $scheduledAtIso,
    ) {
    }
}
