<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

final readonly class RequestPasswordResetInput
{
    public function __construct(
        public string $email,
        public string $appBaseUrl,
    ) {
    }
}
