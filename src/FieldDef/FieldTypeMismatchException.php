<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use RuntimeException;

final class FieldTypeMismatchException extends RuntimeException
{
    public function __construct(
        public readonly string $fieldKey,
        public readonly string $expectedDataType,
        public readonly string $actualDataType,
    ) {
        parent::__construct(
            "Field key \"{$fieldKey}\" expects data type \"{$expectedDataType}\" but is registered as \"{$actualDataType}\".",
        );
    }
}
