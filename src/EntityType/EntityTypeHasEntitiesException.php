<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use RuntimeException;

final class EntityTypeHasEntitiesException extends RuntimeException
{
    public function __construct(int $entityTypeId)
    {
        parent::__construct(
            "Entity type #{$entityTypeId} cannot be deleted because it still has records. Delete all records first.",
        );
    }
}
