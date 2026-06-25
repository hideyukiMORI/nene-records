<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

final readonly class PublicSignupInput
{
    public function __construct(
        public string $organizationName,
        public string $slug,
        public string $email,
        public string $password,
        /** Origin for the verification link, e.g. `https://nene-records.com`. */
        public string $verifyUrlBase = '',
    ) {
    }
}
