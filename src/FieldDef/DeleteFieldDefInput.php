<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class DeleteFieldDefInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
