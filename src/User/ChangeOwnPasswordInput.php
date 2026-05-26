<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class ChangeOwnPasswordInput
{
    public function __construct(
        public string $currentUserEmail,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}
