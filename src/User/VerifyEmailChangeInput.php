<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class VerifyEmailChangeInput
{
    public function __construct(
        public string $token,
    ) {
    }
}
