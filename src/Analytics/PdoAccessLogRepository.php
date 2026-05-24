<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;
use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoAccessLogRepository implements AccessLogRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function insert(AccessLogEntry $entry): void
    {
        $this->query->execute(
            'INSERT INTO access_logs (request_id, method, path, status_code, duration_ms, accessed_at, access_date) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $entry->requestId,
                $entry->method,
                $entry->path,
                $entry->statusCode,
                $entry->durationMs,
                $entry->accessedAt->format(DateTimeImmutable::ATOM),
                $entry->accessedAt->format('Y-m-d'),
            ],
        );
    }

    public function aggregateByDate(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->query->fetchAll(
            <<<'SQL'
            SELECT access_date AS date, COUNT(*) AS request_count, AVG(duration_ms) AS avg_duration_ms
            FROM access_logs
            WHERE access_date >= ? AND access_date <= ?
            GROUP BY access_date
            ORDER BY access_date ASC
            SQL,
            [
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
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
