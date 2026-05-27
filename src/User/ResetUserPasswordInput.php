<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ResetUserPasswordInput
{
    public function __construct(
        public int $id,
        public string $newPassword,
    ) {
    }
}
