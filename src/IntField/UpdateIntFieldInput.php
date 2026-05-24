<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class UpdateIntFieldInput
{
    public function __construct(
        public int $id,
        public string $fieldKey,
        public int $value,
    ) {
    }
}
