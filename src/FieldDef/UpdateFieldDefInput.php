<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class UpdateFieldDefInput
{
    public function __construct(
        public int $id,
        public int $entityTypeId,
        public string $fieldKey,
        public string $dataType,
        public ?int $targetEntityTypeId = null,
        public ?string $cardinality = null,
    ) {
    }
}
