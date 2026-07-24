<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;

/**
 * MySQL-backed daily salt store (ADR 0006). Get-or-create is race-safe via INSERT IGNORE on
 * the `salt_date` primary key: concurrent first-hits of a new day may both generate a salt,
 * but only one row wins and every caller re-reads the persisted value, so the whole day
 * shares one salt. Salts are global (not org-scoped) — org isolation comes from mixing the
 * org id into the hash, not from separate salts.
 */
final readonly class PdoAnalyticsSaltRepository implements AnalyticsSaltRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private ClockInterface $clock,
    ) {
    }

    public function saltForDate(DateTimeImmutable $date): string
    {
        $day = $date->format('Y-m-d');

        $existing = $this->read($day);
        if ($existing !== null) {
            return $existing;
        }

        $this->query->execute(
            'INSERT IGNORE INTO analytics_salts (salt_date, salt, created_at) VALUES (?, ?, ?)',
            [$day, random_bytes(32), $this->clock->now()->format(DateTimeImmutable::ATOM)],
        );

        $stored = $this->read($day);
        if ($stored === null) {
            // Should not happen (we just inserted or someone else did), but never hand back
            // an empty salt — regenerate transiently rather than weaken the hash.
            return random_bytes(32);
        }

        return $stored;
    }

    public function pruneBefore(DateTimeImmutable $cutoff): int
    {
        return $this->query->execute(
            'DELETE FROM analytics_salts WHERE salt_date < ?',
            [$cutoff->format('Y-m-d')],
        );
    }

    private function read(string $day): ?string
    {
        $row = $this->query->fetchOne(
            'SELECT salt FROM analytics_salts WHERE salt_date = ?',
            [$day],
        );

        $salt = $row['salt'] ?? null;

        return is_string($salt) && $salt !== '' ? $salt : null;
    }
}
