<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class GetAccessStatsByDateUseCase implements GetAccessStatsByDateUseCaseInterface
{
    public function __construct(
        private AccessLogRepositoryInterface $accessLogs,
    ) {
    }

    private const BREAKDOWN_LIMIT = 10;

    public function execute(GetAccessStatsByDateInput $input): GetAccessStatsByDateOutput
    {
        $items = $this->accessLogs->aggregateByDate($input->from, $input->to);
        $summary = $this->accessLogs->aggregateVisitorSummary($input->from, $input->to, self::BREAKDOWN_LIMIT);

        // No visitor data collected (opt-in OFF / pre-Path-B range) → expose null so
        // consumers degrade rather than render a misleading all-zero panel.
        $visitor = $this->hasVisitorData($summary) ? $summary : null;

        return new GetAccessStatsByDateOutput(
            from: $input->from->format('Y-m-d'),
            to: $input->to->format('Y-m-d'),
            items: $items,
            visitor: $visitor,
        );
    }

    private function hasVisitorData(VisitorSummary $summary): bool
    {
        return $summary->uniqueVisitors > 0
            || $summary->botRate !== null
            || $summary->topReferrers !== []
            || $summary->utm !== []
            || $summary->ref !== [];
    }
}
