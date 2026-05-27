<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ChangeEmailInput
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {
    }
}
