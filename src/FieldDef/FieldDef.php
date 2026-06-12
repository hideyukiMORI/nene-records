<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use DateTimeImmutable;

final readonly class FieldDef
{
    public function __construct(
        public int $entityTypeId,
        public string $fieldKey,
        public string $dataType,
        public ?int $id = null,
        public bool $isDeleted = false,
        public ?DateTimeImmutable $deletedAt = null,
        public ?int $targetEntityTypeId = null,
        public ?string $cardinality = null,
        public ?string $region = null,
        public int $displayOrder = 0,
    ) {
    }
}
