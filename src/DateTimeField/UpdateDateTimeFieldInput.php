<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class UpdateDateTimeFieldInput
{
    public function __construct(
        public int $id,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
