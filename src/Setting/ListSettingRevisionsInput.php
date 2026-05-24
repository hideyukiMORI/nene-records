<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListSettingRevisionsInput
{
    public function __construct(
        public string $settingKey,
        public int $limit,
        public int $offset,
    ) {
    }
}
