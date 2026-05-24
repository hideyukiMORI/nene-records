<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class ListDateTimeFieldsInput
{
    public function __construct(
        public ?int $entityId = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
