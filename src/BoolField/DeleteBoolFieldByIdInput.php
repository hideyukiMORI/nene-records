<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class DeleteBoolFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
