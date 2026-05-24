<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class GetPublicRecordViewInput
{
    public function __construct(
        public string $entityTypeSlug,
        public int $entityId,
    ) {
    }
}
