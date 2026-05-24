<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class GetDateTimeFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
