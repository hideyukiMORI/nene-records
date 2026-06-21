<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class BlocksField
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public string $value,
        public ?int $id = null,
        public ?string $locale = null,
    ) {
    }
}
