<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

final readonly class InviteUserOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public string $status,
    ) {
    }
}
