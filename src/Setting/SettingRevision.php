<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class SettingRevision
{
    public function __construct(
        public string $settingKey,
        public ?string $value,
        public ?string $previousValue,
        public string $action,
        public ?int $actorUserId,
        public string $createdAt,
        public ?int $id = null,
    ) {
    }
}
