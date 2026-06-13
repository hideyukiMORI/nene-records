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

    /**
     * View counts per entity, derived from GET hits on `/api/v1/entities/{id}`
     * since `$sinceDate` (inclusive, Y-m-d), highest first.
     *
     * @return array<int, int> entityId => viewCount, ordered by viewCount desc
     */
    public function aggregateEntityViews(string $sinceDate): array;
}
