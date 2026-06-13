<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use NeNeRecords\Analytics\AccessLogEntry;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;
use NeNeRecords\Analytics\AccessStatsDayItem;

final class InMemoryAccessLogRepository implements AccessLogRepositoryInterface
{
    /** @var list<AccessLogEntry> */
    private array $entries = [];

    public function insert(AccessLogEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /** @return list<AccessLogEntry> */
    public function all(): array
    {
        return $this->entries;
    }

    public function countByDate(DateTimeImmutable $date): int
    {
        $target = $date->format('Y-m-d');
        $count = 0;

        foreach ($this->entries as $entry) {
            if ($entry->accessedAt->format('Y-m-d') === $target) {
                ++$count;
            }
        }

        return $count;
    }

    public function countByYearMonth(int $year, int $month): int
    {
        $prefix = sprintf('%04d-%02d', $year, $month);
        $count = 0;

        foreach ($this->entries as $entry) {
            if (str_starts_with($entry->accessedAt->format('Y-m-d'), $prefix)) {
                ++$count;
            }
        }

        return $count;
    }

    public function aggregateEntityViews(string $sinceDate): array
    {
        $counts = [];

        foreach ($this->entries as $entry) {
            if ($entry->method !== 'GET' || $entry->statusCode >= 400) {
                continue;
            }
            if ($entry->accessedAt->format('Y-m-d') < $sinceDate) {
                continue;
            }
            if (preg_match('#^/api/v1/entities/(\d+)$#', $entry->path, $matches) !== 1) {
                continue;
            }

            $id = (int) $matches[1];
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    public function aggregateByDate(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        /** @var array<string, list<float>> $byDate */
        $byDate = [];

        foreach ($this->entries as $entry) {
            $date = $entry->accessedAt->format('Y-m-d');

            if ($date < $from->format('Y-m-d') || $date > $to->format('Y-m-d')) {
                continue;
            }

            $byDate[$date] ??= [];
            $byDate[$date][] = $entry->durationMs;
        }

        ksort($byDate);

        $items = [];

        foreach ($byDate as $date => $durations) {
            $items[] = new AccessStatsDayItem(
                date: $date,
                requestCount: count($durations),
                avgDurationMs: round(array_sum($durations) / count($durations), 3),
            );
        }

        return $items;
    }
}
