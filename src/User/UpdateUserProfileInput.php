<?php

declare(strict_types=1);

namespace NeNeRecords\User;

final readonly class UpdateUserProfileInput
{
    public function __construct(
        public int $userId,
        public ?string $displayName,
        public ?string $fullName,
        public ?string $jobTitle,
    ) {
    }
}
