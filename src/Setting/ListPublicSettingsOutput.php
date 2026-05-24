<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListPublicSettingsOutput
{
    /** @param list<SettingEntry> $items */
    public function __construct(
        public array $items,
    ) {
    }
}
