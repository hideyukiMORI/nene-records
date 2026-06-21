<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class GetBlocksFieldByIdOutput
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $fieldKey,
        public string $value,
        public ?string $locale = null,
    ) {
    }
}
