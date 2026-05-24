<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class DeleteDateTimeFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
