<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class ListDateTimeFieldItem
{
    public function __construct(
        public int $id,
        public int $entityId,
        public string $fieldKey,
        public string $value,
    ) {
    }
}
