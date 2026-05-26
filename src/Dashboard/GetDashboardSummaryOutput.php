<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

final readonly class GetDashboardSummaryOutput
{
    /**
     * @param list<DashboardRecentEntity>      $recentPublished
     * @param list<DashboardEntityTypeSummary> $entityTypeSummary
     */
    public function __construct(
        public array $recentPublished,
        public int $todayAccessCount,
        public int $thisMonthAccessCount,
        public array $entityTypeSummary,
    ) {
    }
}
