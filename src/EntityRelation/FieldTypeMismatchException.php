<?php

declare(strict_types=1);

namespace NeNeRecords\EntityRelation;

use RuntimeException;

final class FieldTypeMismatchException extends RuntimeException
{
    public function __construct(
        public readonly string $fieldKey,
        public readonly string $expectedDataType,
        public readonly string $actualDataType,
    ) {
        parent::__construct(sprintf(
            'Field key "%s" expects data type "%s" but is registered as "%s".',
            $fieldKey,
            $expectedDataType,
            $actualDataType,
        ));
    }
}
