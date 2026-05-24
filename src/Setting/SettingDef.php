<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class SettingDef
{
    public function __construct(
        public string $settingKey,
        public string $dataType,
        public ?string $defaultValue,
        public bool $isPublic,
        public string $label,
        public ?int $id = null,
    ) {
    }
}
