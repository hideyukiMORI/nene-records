<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use RuntimeException;

final class EntityTypeHasEntitiesException extends RuntimeException
{
    public function __construct(int $entityTypeId, int $count)
    {
        parent::__construct(
            "Entity type #{$entityTypeId} cannot be deleted because it has {$count} record(s). Delete all records first.",
        );
    }
}
