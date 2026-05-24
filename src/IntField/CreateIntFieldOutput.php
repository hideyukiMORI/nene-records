<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class CreateIntFieldOutput
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $fieldKey,
        public int $value,
    ) {
    }
}
