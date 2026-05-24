<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class ListBoolFieldsInput
{
    public function __construct(
        public ?int $entityId = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
