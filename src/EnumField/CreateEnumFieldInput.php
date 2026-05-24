<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class CreateEnumFieldInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
