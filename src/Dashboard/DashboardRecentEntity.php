<?php

declare(strict_types=1);

namespace NeNeRecords\Dashboard;

final readonly class DashboardRecentEntity
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public string $entityTypeName,
        public string $entityTypeSlug,
        public ?string $slug,
        public ?string $publishedAtIso,
    ) {
    }
}
