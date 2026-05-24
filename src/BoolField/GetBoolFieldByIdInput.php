<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class GetBoolFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
