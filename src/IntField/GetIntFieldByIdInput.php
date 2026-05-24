<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class GetIntFieldByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
