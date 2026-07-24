<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Analytics;

use DateTimeImmutable;
use NeNeRecords\Analytics\AccessLogEntry;
use NeNeRecords\Analytics\AccessLogRepositoryInterface;
use NeNeRecords\Analytics\AccessStatsDayItem;
use NeNeRecords\Analytics\VisitorSummary;

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

    public function aggregateVisitorSummary(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): VisitorSummary
    {
        $fromDay = $from->format('Y-m-d');
        $toDay = $to->format('Y-m-d');

        $hashes = [];
        $botFlags = [];
        $referrers = [];
        $utm = [];
        $refs = [];

        foreach ($this->entries as $entry) {
            $day = $entry->accessedAt->format('Y-m-d');
            if ($day < $fromDay || $day > $toDay) {
                continue;
            }

            if ($entry->visitorHash !== null) {
                $hashes[$entry->visitorHash] = true;
            }
            if ($entry->isBot !== null) {
                $botFlags[] = $entry->isBot ? 1 : 0;
            }
            if ($entry->refererHost !== null) {
                $referrers[$entry->refererHost] = ($referrers[$entry->refererHost] ?? 0) + 1;
            }
            if ($entry->utmSource !== null || $entry->utmMedium !== null || $entry->utmCampaign !== null) {
                $key = ($entry->utmSource ?? '') . "\0" . ($entry->utmMedium ?? '') . "\0" . ($entry->utmCampaign ?? '');
                $utm[$key] ??= ['source' => $entry->utmSource, 'medium' => $entry->utmMedium, 'campaign' => $entry->utmCampaign, 'count' => 0];
                ++$utm[$key]['count'];
            }
            if ($entry->ref !== null) {
                $refs[$entry->ref] = ($refs[$entry->ref] ?? 0) + 1;
            }
        }

        arsort($referrers);
        arsort($refs);
        usort($utm, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        $topReferrers = [];
        foreach (array_slice($referrers, 0, $limit, true) as $host => $count) {
            $topReferrers[] = ['host' => (string) $host, 'count' => $count];
        }

        $refItems = [];
        foreach (array_slice($refs, 0, $limit, true) as $ref => $count) {
            $refItems[] = ['ref' => (string) $ref, 'count' => $count];
        }

        return new VisitorSummary(
            uniqueVisitors: count($hashes),
            botRate: $botFlags === [] ? null : round(array_sum($botFlags) / count($botFlags), 4),
            topReferrers: $topReferrers,
            utm: array_slice($utm, 0, $limit),
            ref: $refItems,
        );
    }

    public function statusDistribution(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $fromDay = $from->format('Y-m-d');
        $toDay = $to->format('Y-m-d');
        $out = [];

        foreach ($this->entries as $entry) {
            $day = $entry->accessedAt->format('Y-m-d');
            if ($day < $fromDay || $day > $toDay) {
                continue;
            }
            $key = intdiv($entry->statusCode, 100) . 'xx';
            $out[$key] = ($out[$key] ?? 0) + 1;
        }

        ksort($out);

        return $out;
    }

    public function popularPages(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        $fromDay = $from->format('Y-m-d');
        $toDay = $to->format('Y-m-d');
        $counts = [];

        foreach ($this->entries as $entry) {
            $day = $entry->accessedAt->format('Y-m-d');
            if ($day < $fromDay || $day > $toDay) {
                continue;
            }
            if (!in_array($entry->method, ['GET', 'BEACON'], true) || $entry->statusCode >= 400) {
                continue;
            }
            if (
                str_starts_with($entry->path, '/api/')
                || str_starts_with($entry->path, '/media/')
                || in_array($entry->path, ['/robots.txt', '/sitemap.xml'], true)
            ) {
                continue;
            }
            $counts[$entry->path] = ($counts[$entry->path] ?? 0) + 1;
        }

        arsort($counts);

        $out = [];
        foreach (array_slice($counts, 0, $limit, true) as $path => $count) {
            $out[] = ['path' => (string) $path, 'count' => $count];
        }

        return $out;
    }
}
