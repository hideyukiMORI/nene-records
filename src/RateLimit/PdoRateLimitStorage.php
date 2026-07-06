<?php

declare(strict_types=1);

namespace NeNeRecords\RateLimit;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Middleware\RateLimitStorageInterface;

/**
 * Database-backed rate limit storage.
 *
 * Uses a fixed-window algorithm: each key maps to a (count, reset_at) pair
 * stored in the `rate_limits` table. Expired windows are reset on the next hit.
 *
 * **Atomicity**: the upsert uses INSERT … ON DUPLICATE KEY UPDATE which is
 * atomic in MySQL/MariaDB. A subsequent SELECT retrieves the committed state.
 * Under very high concurrency two requests may read slightly stale counts but
 * this is acceptable for soft rate limiting.
 */
final readonly class PdoRateLimitStorage implements RateLimitStorageInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return array{count: int, reset_at: int}
     */
    public function hit(string $key, int $windowSeconds): array
    {
        $keyHash = hash('sha256', $key);
        $now = $this->clock->now()->getTimestamp();
        $newResetAt = $now + $windowSeconds;

        // Upsert: insert with count=1, or increment if the window is still open,
        // or reset to 1 if the window has expired.
        $this->query->execute(
            <<<'SQL'
                INSERT INTO rate_limits (key_hash, count, reset_at)
                VALUES (?, 1, ?)
                ON DUPLICATE KEY UPDATE
                    count    = IF(reset_at <= ?, 1, count + 1),
                    reset_at = IF(reset_at <= ?, ?, reset_at)
                SQL,
            [$keyHash, $newResetAt, $now, $now, $newResetAt],
        );

        $row = $this->query->fetchOne(
            'SELECT count, reset_at FROM rate_limits WHERE key_hash = ?',
            [$keyHash],
        );

        return [
            'count'    => (int) ($row['count'] ?? 1),
            'reset_at' => (int) ($row['reset_at'] ?? $newResetAt),
        ];
    }
}
