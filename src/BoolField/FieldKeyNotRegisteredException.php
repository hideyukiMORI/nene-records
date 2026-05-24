<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

use RuntimeException;

final class FieldKeyNotRegisteredException extends RuntimeException
{
    public function __construct(
        public int $entityTypeId,
        public string $fieldKey,
    ) {
        parent::__construct("Field key {$fieldKey} is not registered for entity type {$entityTypeId}.");
    }
}
