<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

/**
 * Range-level visitor aggregates derived from the Path B fields (ADR 0006 / #1007).
 *
 * All figures come from privacy-preserving derived columns only — never raw IP/UA/referer/
 * query. `uniqueVisitors` counts distinct daily-salted hashes; because the salt rotates
 * daily this is a per-day-union count, not a stable cross-day identity. Returned by the
 * stats API and the export CLI; null at the call site when the org has collected no visitor
 * data (opt-in OFF or pre-Path-B range).
 */
final readonly class VisitorSummary
{
    /**
     * @param list<array{host: string, count: int}>                                    $topReferrers
     * @param list<array{source: ?string, medium: ?string, campaign: ?string, count: int}> $utm
     * @param list<array{ref: string, count: int}>                                     $ref
     */
    public function __construct(
        public int $uniqueVisitors,
        public ?float $botRate,
        public array $topReferrers,
        public array $utm,
        public array $ref,
    ) {
    }
}
