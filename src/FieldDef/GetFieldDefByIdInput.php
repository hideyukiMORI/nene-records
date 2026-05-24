<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class GetFieldDefByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
