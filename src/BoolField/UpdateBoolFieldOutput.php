<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class UpdateBoolFieldOutput
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $fieldKey,
        public bool $value,
    ) {
    }
}
