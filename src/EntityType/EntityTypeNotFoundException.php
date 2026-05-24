<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use RuntimeException;

final class EntityTypeNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Entity type with id {$id} was not found.");
    }
}
