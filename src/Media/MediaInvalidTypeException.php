<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use DomainException;

final class MediaInvalidTypeException extends DomainException
{
    public function __construct(public readonly string $mimeType)
    {
        parent::__construct('Unsupported media type: ' . $mimeType);
    }
}
