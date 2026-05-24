<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

final readonly class ListSettingRevisionsOutput
{
    /** @param list<SettingRevision> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
    ) {
    }
}
