<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class CreateBlocksFieldInput
{
    public function __construct(
        public int $entityId,
        public string $fieldKey,
        public string $value,
        public ?string $locale = null,
    ) {
    }
}
