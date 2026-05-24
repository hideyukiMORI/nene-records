<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class SettingEntry
{
    public function __construct(
        public SettingDef $def,
        public string $effectiveValue,
        public ?SettingValue $storedValue,
    ) {
    }
}
