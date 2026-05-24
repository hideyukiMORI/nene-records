<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class DeleteEnumFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
