<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use DateTimeImmutable;

/** One row of an entity type's public archive listing (#877). */
final readonly class PublicTypeArchiveItem
{
    public function __construct(
        public int $id,
        public string $label,
        /** Canonical public path, e.g. "/privacy" or "/posts/42". */
        public string $path,
        public ?DateTimeImmutable $publishedAt,
    ) {
    }
}
