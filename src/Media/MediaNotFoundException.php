<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use DomainException;

final class MediaNotFoundException extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct('Media not found: ' . $id);
    }
}
