<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class UpdateEnumFieldInput
{
    public function __construct(
        public int $id,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
