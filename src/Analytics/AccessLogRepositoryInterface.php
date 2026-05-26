<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;

interface AccessLogRepositoryInterface
{
    public function insert(AccessLogEntry $entry): void;

    /**
     * @return list<AccessStatsDayItem>
     */
    public function aggregateByDate(DateTimeImmutable $from, DateTimeImmutable $to): array;

    public function countByDate(DateTimeImmutable $date): int;

    public function countByYearMonth(int $year, int $month): int;
}
