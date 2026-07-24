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

    /**
     * Range-level privacy-first visitor aggregates (ADR 0006). Breakdown lists are capped at
     * `$limit`, highest first. Derived from Path B columns only (no raw PII).
     */
    public function aggregateVisitorSummary(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): VisitorSummary;

    /**
     * Request counts grouped by HTTP status class ("2xx".."5xx") over the range.
     *
     * @return array<string, int>
     */
    public function statusDistribution(DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Most-visited public page paths over the range (GET + LP BEACON hits, status < 400),
     * excluding API, media, robots and sitemap. Highest first, capped at `$limit`.
     *
     * @return list<array{path: string, count: int}>
     */
    public function popularPages(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array;
}
