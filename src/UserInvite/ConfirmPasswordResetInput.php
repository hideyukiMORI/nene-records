<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

final readonly class ConfirmPasswordResetInput
{
    public function __construct(
        public string $token,
        public string $newPassword,
    ) {
    }
}
