<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use DateTimeImmutable;

/**
 * Store for the per-day visitor-hash salt (ADR 0006 D2 / D6).
 */
interface AnalyticsSaltRepositoryInterface
{
    /**
     * Returns the 32-byte raw salt for the given calendar day, creating it on first use.
     * Stable within a day (so all hashes that day are comparable), rotated across days.
     */
    public function saltForDate(DateTimeImmutable $date): string;

    /**
     * Deletes salts dated strictly before the cutoff (retention prune). Returns rows removed.
     */
    public function pruneBefore(DateTimeImmutable $cutoff): int;
}
