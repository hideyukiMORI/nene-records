<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use RuntimeException;

final class PublicRecordNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $entityTypeSlug,
        public readonly string $entitySlug,
    ) {
        parent::__construct(
            "Public record \"{$entityTypeSlug}/{$entitySlug}\" was not found.",
        );
    }
}
