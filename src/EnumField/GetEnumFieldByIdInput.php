<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class GetEnumFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
