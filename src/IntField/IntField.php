<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class IntField
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public int $value,
        public ?int $id = null,
    ) {
    }
}
