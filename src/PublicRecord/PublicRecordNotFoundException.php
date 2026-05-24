<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use RuntimeException;

final class PublicRecordNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $entityTypeSlug,
        public readonly int $entityId,
    ) {
        parent::__construct(
            "Public record \"{$entityTypeSlug}/{$entityId}\" was not found.",
        );
    }
}
