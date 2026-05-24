<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class ListFieldDefsInput
{
    public function __construct(
        public ?int $entityTypeId,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}
