<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

final readonly class DashboardEntityTypeSummary
{
    public function __construct(
        public int $entityTypeId,
        public string $entityTypeName,
        public string $entityTypeSlug,
        public int $publishedCount,
        public int $draftCount,
    ) {
    }
}
