<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use RuntimeException;

/**
 * Raised when a media item is still referenced by one or more entity fields and
 * therefore cannot be safely deleted.
 */
final class MediaInUseException extends RuntimeException
{
    /**
     * @param list<MediaUsage> $usages
     */
    public function __construct(
        public readonly int $mediaId,
        public readonly array $usages,
    ) {
        parent::__construct("Media {$mediaId} is still in use by " . count($usages) . ' field(s).');
    }
}
