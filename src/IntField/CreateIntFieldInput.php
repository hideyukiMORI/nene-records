<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class CreateIntFieldInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public int $value,
    ) {
    }
}
