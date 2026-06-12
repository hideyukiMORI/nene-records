<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

/**
 * A single place where a media item is referenced: one entity field
 * (image / file / markdown body) whose stored value contains the media URL.
 */
final readonly class MediaUsage
{
    public function __construct(
        public int $entityId,
        public string $entityTypeSlug,
        public string $entitySlug,
        public string $status,
        public string $fieldKey,
        public ?string $title,
    ) {
    }
}
