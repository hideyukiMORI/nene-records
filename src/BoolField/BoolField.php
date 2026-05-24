<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class BoolField
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public bool $value,
        public ?int $id = null,
    ) {
    }
}
