<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use RuntimeException;

final class DuplicateEntitySlugException extends RuntimeException
{
    public function __construct(string $slug)
    {
        parent::__construct("Entity slug \"{$slug}\" already exists in this entity type.");
    }
}
