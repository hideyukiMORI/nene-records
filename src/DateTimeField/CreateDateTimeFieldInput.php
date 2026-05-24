<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class CreateDateTimeFieldInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
