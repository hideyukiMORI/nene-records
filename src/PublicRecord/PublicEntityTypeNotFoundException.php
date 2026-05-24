<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use RuntimeException;

final class PublicEntityTypeNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $slug)
    {
        parent::__construct("Entity type with slug \"{$slug}\" was not found.");
    }
}
