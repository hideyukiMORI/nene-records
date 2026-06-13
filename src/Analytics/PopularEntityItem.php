<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class PopularEntityItem
{
    public function __construct(
        public int $entityId,
        public int $entityTypeId,
        public ?string $slug,
        public ?string $publishedAtIso,
        public ?string $title,
        public int $viewCount,
    ) {
    }
}
