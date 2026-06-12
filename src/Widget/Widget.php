<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class Widget
{
    /**
     * @param array<string, mixed> $settings widget-type-specific config
     */
    public function __construct(
        public ?int $id,
        public string $widgetType,
        public string $region,
        public int $displayOrder,
        public ?string $title,
        public array $settings,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
