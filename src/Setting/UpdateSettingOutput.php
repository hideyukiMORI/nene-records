<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class UpdateSettingOutput
{
    public function __construct(
        public string $settingKey,
        public string $value,
        public string $updatedAt,
    ) {
    }
}
