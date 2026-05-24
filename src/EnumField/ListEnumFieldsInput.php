<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class ListEnumFieldsInput
{
    public function __construct(
        public ?int $entityId = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
