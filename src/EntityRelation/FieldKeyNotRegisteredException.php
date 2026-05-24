<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use RuntimeException;

final class FieldKeyNotRegisteredException extends RuntimeException
{
    public function __construct(
        public readonly string $fieldKey,
    ) {
        parent::__construct(sprintf('Field key "%s" is not registered for this entity type.', $fieldKey));
    }
}
