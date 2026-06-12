<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

final readonly class UpdateWidgetInput
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public int $id,
        public string $widgetType,
        public string $region,
        public int $displayOrder,
        public ?string $title,
        public array $settings,
    ) {
    }
}
