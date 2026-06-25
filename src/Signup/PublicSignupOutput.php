<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

final readonly class PublicSignupOutput
{
    public function __construct(
        public string $token,
        public int $expiresAt,
        public int $organizationId,
        public string $slug,
        public string $email,
        public string $role,
    ) {
    }
}
