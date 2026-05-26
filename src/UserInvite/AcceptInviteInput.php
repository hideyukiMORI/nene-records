<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

final readonly class AcceptInviteInput
{
    public function __construct(
        public string $token,
        public string $password,
    ) {
    }
}
