<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class UpdateSettingInput
{
    public function __construct(
        public string $settingKey,
        public string $value,
        public ?int $actorUserId = null,
    ) {
    }
}
