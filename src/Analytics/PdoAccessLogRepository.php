<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoAccessLogRepository implements AccessLogRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function insert(AccessLogEntry $entry): void
    {
        $this->query->execute(
            'INSERT INTO access_logs (organization_id, request_id, method, path, status_code, duration_ms, accessed_at, access_date, visitor_hash, referer_host, utm_source, utm_medium, utm_campaign, ref, client_type, is_bot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->orgId->get(),
                $entry->requestId,
                $entry->method,
                $entry->path,
                $entry->statusCode,
                $entry->durationMs,
                $entry->accessedAt->format(DateTimeImmutable::ATOM),
                $entry->accessedAt->format('Y-m-d'),
                $entry->visitorHash,
                $entry->refererHost,
                $entry->utmSource,
                $entry->utmMedium,
                $entry->utmCampaign,
                $entry->ref,
                $entry->clientType,
                $entry->isBot === null ? null : ($entry->isBot ? 1 : 0),
            ],
        );
    }

    public function countByDate(DateTimeImmutable $date): int
    {
        $row = $this->query->fetchOne(
            'SELECT COUNT(*) AS cnt FROM access_logs WHERE access_date = ? AND organization_id = ?',
            [$date->format('Y-m-d'), $this->orgId->get()],
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function countByYearMonth(int $year, int $month): int
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = (int) date('t', (int) mktime(0, 0, 0, $month, 1, $year));
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);

        $row = $this->query->fetchOne(
            'SELECT COUNT(*) AS cnt FROM access_logs WHERE access_date >= ? AND access_date <= ? AND organization_id = ?',
            [$from, $to, $this->orgId->get()],
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function aggregateEntityViews(string $sinceDate): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
            SELECT path, COUNT(*) AS cnt
            FROM access_logs
            WHERE organization_id = ?
              AND method = 'GET'
              AND status_code < 400
              AND access_date >= ?
              AND path LIKE '/api/v1/entities/%'
            GROUP BY path
            SQL,
            [$this->orgId->get(), $sinceDate],
        );

        $counts = [];
        foreach ($rows as $row) {
            // Only bare record-detail paths (/api/v1/entities/42), not nested
            // ones such as /api/v1/entities/42/revisions.
            if (preg_match('#^/api/v1/entities/(\d+)$#', (string) $row['path'], $matches) === 1) {
                $id = (int) $matches[1];
                $counts[$id] = ($counts[$id] ?? 0) + (int) $row['cnt'];
            }
        }

        arsort($counts);

        return $counts;
    }

    public function aggregateByDate(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
            SELECT access_date AS date, COUNT(*) AS request_count, AVG(duration_ms) AS avg_duration_ms
            FROM access_logs
            WHERE access_date >= ? AND access_date <= ? AND organization_id = ?
            GROUP BY access_date
            ORDER BY access_date ASC
            SQL,
            [
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
                $this->orgId->get(),
            ],
        );

        return array_map(
            static fn (array $row): AccessStatsDayItem => new AccessStatsDayItem(
                date: (string) $row['date'],
                requestCount: (int) $row['request_count'],
                avgDurationMs: round((float) $row['avg_duration_ms'], 3),
            ),
            $rows,
        );
    }

    public function aggregateVisitorSummary(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): VisitorSummary
    {
        $fromDay = $from->format('Y-m-d');
        $toDay = $to->format('Y-m-d');
        $org = $this->orgId->get();
        // $limit is an internal integer (not user input); inlined because MySQL rejects a
        // bound placeholder in LIMIT under emulated prepares.
        $cap = max(1, $limit);

        $unique = $this->query->fetchOne(
            'SELECT COUNT(DISTINCT visitor_hash) AS c FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ? AND visitor_hash IS NOT NULL',
            [$fromDay, $toDay, $org],
        );

        $bot = $this->query->fetchOne(
            'SELECT AVG(is_bot) AS r FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ? AND is_bot IS NOT NULL',
            [$fromDay, $toDay, $org],
        );
        $botRateRaw = $bot['r'] ?? null;
        $botRate = $botRateRaw === null ? null : round((float) $botRateRaw, 4);

        $referrers = $this->query->fetchAll(
            'SELECT referer_host AS host, COUNT(*) AS cnt FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ? AND referer_host IS NOT NULL
             GROUP BY referer_host ORDER BY cnt DESC, referer_host ASC LIMIT ' . $cap,
            [$fromDay, $toDay, $org],
        );

        $utm = $this->query->fetchAll(
            'SELECT utm_source, utm_medium, utm_campaign, COUNT(*) AS cnt FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ?
               AND (utm_source IS NOT NULL OR utm_medium IS NOT NULL OR utm_campaign IS NOT NULL)
             GROUP BY utm_source, utm_medium, utm_campaign ORDER BY cnt DESC LIMIT ' . $cap,
            [$fromDay, $toDay, $org],
        );

        $refs = $this->query->fetchAll(
            'SELECT ref, COUNT(*) AS cnt FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ? AND ref IS NOT NULL
             GROUP BY ref ORDER BY cnt DESC, ref ASC LIMIT ' . $cap,
            [$fromDay, $toDay, $org],
        );

        return new VisitorSummary(
            uniqueVisitors: (int) ($unique['c'] ?? 0),
            botRate: $botRate,
            topReferrers: array_map(
                static fn (array $r): array => ['host' => (string) $r['host'], 'count' => (int) $r['cnt']],
                $referrers,
            ),
            utm: array_map(
                static fn (array $r): array => [
                    'source' => $r['utm_source'] === null ? null : (string) $r['utm_source'],
                    'medium' => $r['utm_medium'] === null ? null : (string) $r['utm_medium'],
                    'campaign' => $r['utm_campaign'] === null ? null : (string) $r['utm_campaign'],
                    'count' => (int) $r['cnt'],
                ],
                $utm,
            ),
            ref: array_map(
                static fn (array $r): array => ['ref' => (string) $r['ref'], 'count' => (int) $r['cnt']],
                $refs,
            ),
        );
    }

    public function statusDistribution(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->query->fetchAll(
            'SELECT FLOOR(status_code / 100) AS cls, COUNT(*) AS cnt FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ?
             GROUP BY cls ORDER BY cls ASC',
            [$from->format('Y-m-d'), $to->format('Y-m-d'), $this->orgId->get()],
        );

        $out = [];
        foreach ($rows as $row) {
            $out[((int) $row['cls']) . 'xx'] = (int) $row['cnt'];
        }

        return $out;
    }

    public function popularPages(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        $cap = max(1, $limit);
        $rows = $this->query->fetchAll(
            "SELECT path, COUNT(*) AS cnt FROM access_logs
             WHERE access_date >= ? AND access_date <= ? AND organization_id = ?
               AND method IN ('GET', 'BEACON') AND status_code < 400
               AND path NOT LIKE '/api/%' AND path NOT LIKE '/media/%'
               AND path NOT IN ('/robots.txt', '/sitemap.xml')
             GROUP BY path ORDER BY cnt DESC, path ASC LIMIT " . $cap,
            [$from->format('Y-m-d'), $to->format('Y-m-d'), $this->orgId->get()],
        );

        return array_map(
            static fn (array $r): array => ['path' => (string) $r['path'], 'count' => (int) $r['cnt']],
            $rows,
        );
    }
}
