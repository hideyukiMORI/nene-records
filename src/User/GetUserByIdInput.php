<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class GetUserByIdInput
{
    public function __construct(
        public int $id,
    ) {
    }
}
