<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ChangePasswordInput
{
    public function __construct(
        public string $currentUserEmail,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}
