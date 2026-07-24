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
}
