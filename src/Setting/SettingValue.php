<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class SettingValue
{
    public function __construct(
        public string $settingKey,
        public ?string $value,
        public bool $isDeleted,
        public ?string $deletedAt,
        public ?int $createdBy,
        public ?int $updatedBy,
        public string $createdAt,
        public string $updatedAt,
        public ?int $id = null,
    ) {
    }
}
