<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class UpdateBlocksFieldInput
{
    public function __construct(
        public int $id,
        public string $fieldKey,
        public string $value,
        public ?string $locale = null,
    ) {
    }
}
