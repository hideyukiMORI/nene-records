<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class CreateFieldDefOutput
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public string $fieldKey,
        public string $dataType,
    ) {
    }
}
