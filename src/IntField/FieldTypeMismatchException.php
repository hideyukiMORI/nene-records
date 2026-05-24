<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

use RuntimeException;

final class FieldTypeMismatchException extends RuntimeException
{
    public function __construct(
        public string $fieldKey,
        public string $expectedDataType,
        public string $actualDataType,
    ) {
        parent::__construct("Field key {$fieldKey} has data type {$actualDataType}, expected {$expectedDataType}.");
    }
}
