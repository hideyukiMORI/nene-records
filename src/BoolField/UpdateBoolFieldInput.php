<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class UpdateBoolFieldInput
{
    public function __construct(
        public int $id,
        public string $fieldKey,
        public bool $value,
    ) {
    }
}
