<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class CreateFieldDefInput
{
    public function __construct(
        public int $entityTypeId,
        public string $fieldKey,
        public string $dataType,
        public ?int $targetEntityTypeId = null,
        public ?string $cardinality = null,
        public ?string $region = null,
        public int $displayOrder = 0,
    ) {
    }
}
