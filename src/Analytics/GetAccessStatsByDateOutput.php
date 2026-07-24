<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class GetAccessStatsByDateOutput
{
    /**
     * @param list<AccessStatsDayItem> $items
     */
    public function __construct(
        public string $from,
        public string $to,
        public array $items,
        // Path B (ADR 0006). Null when the org has collected no visitor data
        // (opt-in OFF or a range predating Path B) — the frontend/consumer degrades gracefully.
        public ?VisitorSummary $visitor = null,
    ) {
    }
}
