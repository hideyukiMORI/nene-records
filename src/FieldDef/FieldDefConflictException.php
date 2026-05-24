<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use RuntimeException;

final class FieldDefConflictException extends RuntimeException
{
    public function __construct(
        public int $entityTypeId,
        public string $fieldKey,
    ) {
        parent::__construct("A field definition with key {$fieldKey} already exists for entity type {$entityTypeId}.");
    }
}
