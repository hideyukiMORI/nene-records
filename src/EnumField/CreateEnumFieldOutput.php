<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class CreateEnumFieldOutput
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
