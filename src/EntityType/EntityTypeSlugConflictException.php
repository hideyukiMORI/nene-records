<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use RuntimeException;

final class EntityTypeSlugConflictException extends RuntimeException
{
    public function __construct(
        public string $slug,
    ) {
        parent::__construct("An entity type with slug {$slug} already exists.");
    }
}
