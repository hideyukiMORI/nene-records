<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use DomainException;

final class MediaTooLargeException extends DomainException
{
    public function __construct(
        public readonly int $actualBytes,
        public readonly int $maxBytes,
    ) {
        parent::__construct(sprintf('File size %d exceeds maximum of %d bytes.', $actualBytes, $maxBytes));
    }
}
