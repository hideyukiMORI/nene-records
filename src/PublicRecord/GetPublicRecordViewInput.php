<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

final readonly class GetPublicRecordViewInput
{
    public function __construct(
        public string $entityTypeSlug,
        public ?string $entitySlug = null,
        public ?int $entityId = null,
        /** Negotiated content locale (#540); null = base / locale-agnostic rows. */
        public ?string $locale = null,
    ) {
    }
}
