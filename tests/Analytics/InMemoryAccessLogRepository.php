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
