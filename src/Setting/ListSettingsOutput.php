<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListSettingsOutput
{
    /** @param list<SettingEntry> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
